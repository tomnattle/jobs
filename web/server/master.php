<?php
namespace server;

use ActiveRecord\Config;
use util\configManager;
use util\log;
use event\eventManager;
use server\event\server;
use server\swooleServer;
use environment\prox as environmentProx;
use command\command;
use util\id;
use api\master as apiMaster;
use api\slaver as apiSlaver;
use api\slaver;
use client\clientFactory;
use sync\sync;
use api\apiFactory;
use task\taskManager;
use executer\executerFactory;

class master implements Imaster
{
    // 状态
    public $status;
    // 配置
    public $config;
    // uuid
    static $uuid = '';
    // 工作数量
    static $workerCount = null;
    // id
    private $id;
    // 处理进程
    private $processes = [];
    // 服务器
    private $server;
    // 环境
    private $environment;

    // 初始化
    public function __construct()
    {
        self::$workerCount = new \swoole_atomic(0);
        $this->id = id::gen(self::class);
        self::$uuid = $this->id;
    }
    
    // 初始化
    public function init()
    {
        $this->registerErro();
        $this->environment = new environmentProx($this);
        sync::config($this->environment);
        $this->loadConfig();
        $this->initDd();
        $this->initCallBack();
        $this->initServer();
    }
    
    // loadconfig
    public function loadConfig()
    {
        $this->config = configManager::loadConfig("master");
    }
    // 初始化服务器
    public function initServer()
    {
        $this->server = swooleServer::create($this);
    }

    // 初始化数据库
    public function initDd()
    {
        $cfg = Config::instance();
        $cfg->set_model_directory(ROOT . "/api");
        $cfg->set_connections(\util\configManager::loadSysConfig("conn"));
        Config::initialize(function ($cfg) {
            $cfg->set_default_connection(ENV);
        });
    }
    // 出实话回调
    public function initCallBack()
    {
        // 启动后 同步
        eventManager::addCallBack(server::AFTER_START_UP, function ($server) {
            sync::put(sync::$node_root . "master.pid", $server->master_pid);
        });
        
        // 如果服务器启动完毕 注册伺服
        eventManager::addCallBack(server::AFTER_START_UP, function ($server) {
            try {
                // 注册为备用master
                if ($this->config['role'] == 'standbyer') {
                    $master = $this->config['master'];
                    $client = clientFactory::create($master['ip'], $master['port']);
                    $cmd = command::create("registerStandby", [
                        'pid' => $server->master_pid,
                        'uuid' => $this->id,
                        'ip' => $this->config['ip'],
                        'port' => $this->config['port']
                    ], - 1, - 1, 'register for a standby');
                    $client->send($cmd->toString());
                    $recv = $client->recv();
                    $client->close();
                    $cmd = command::purse($recv);
                    if (! $cmd->_success)
                        throw new \Exception('regitster failure, ' . $cmd->_message);
                    log::write('remote-server', $cmd->_message);
                    sync::put(sync::$isStandby, date('Y-m-d H:i:s'));
                } else {
                    // 纪录节点
                    sync::put(sync::$masterDir . 'master.info', json_encode([
                        'pid' => $server->master_pid,
                        'uuid' => $this->id,
                        'ip' => $this->config['ip'],
                        'port' => $this->config['port']
                    ]));
                    sync::put(sync::$isMaster, date('Y-m-d H:i:s'));
                }
            } catch (\Exception $e) {
                log::write('register standby', 'target server is not valid. ' . $e->getMessage());
                // 服务器关闭
                $server->shutdown();
                exit();
            }
        });
        // 启动后
        eventManager::addCallBack(server::AFTER_START_UP, function () {
            try {
                // 存库
                apiMaster::update_all([
                        'set' => [
                            '_status' => status::_EXIT
                        ],
                        'conditions' => [
                            '_status' => status::_WORKING
                        ]
                    ]);

                apiMaster::add($this->id, $this->config['ip'], $this->config['port'], $this->config['role'], status::_WORKING, date("Y-m-d H:i:s"), date("Y-m-d H:i:s"), '');
            } catch (\Exception $e) {
                log::write('insert master', 'faile:' . $e->getMessage());
                exit();
            }
        });
        // 同步
        eventManager::addCallBack(server::SYNC, function ($server) {
            if ($server) {
                // 延时100秒
                $server->after(100, function () {
                    $this->sync();
                });
            } else {
                $this->sync();
            }
        });
        // 备用机变为master
        eventManager::addCallBack(server::STANDBYER_TO_MASTER, function ($data) {
            $_master = $data['current-master'];
            $server = $data['server'];
            $oldMaster = json_decode(sync::get(sync::$masterDir . 'master.info'));
            // 同步
            $info = sync::get(sync::$standbyDir . $_master);
            sync::put(sync::$masterDir . 'master.info', $info);
            
            // 更新master角色
            $_oldMaster = apiMaster::find([
                'uuid' => $oldMaster->uuid
            ]);
            $_oldMaster->_status = status::_EXIT;
            $_oldMaster->save();
            // 存库
            $master = apiMaster::find([
                'uuid' => $this->id
            ]);
            $master->role = 'master';
            $master->_updated = date('Y-m-d H:i:s');
            $master->save();
            log::write('set-master', 'new master is ' . $this->id);
            
            // 更新slaver关系
            $slavers = slaver::all([
                'conditions' => " master_uuid = '" . $oldMaster->uuid . "'"
            ]);
            // 更新slaver对应的masterid
            foreach ($slavers as $slaver) {
                $slaver->master_uuid = $this->id;
                $slaver->SAVE();
            }
            log::write('update-slaver', 'update master slaver relation.');
            
            // 清空本身
            sync::del(sync::$standbyDir . $_master);
            sync::put(sync::$isMaster, date('Y-m-d H:i:s'));
            sync::del(sync::$isStandby);
            // 触发同步
            eventManager::triggle(server::SYNC, $server);
        });
        // 主机丢失
        eventManager::addCallBack(server::MASTER_LOST, function ($data) {
            log::write('master die', 'old master is [' . $this->config['master']['ip'] . '-' . $this->config['master']['port'] . ']');
            $standbys = sync::loadStandbys();
            $max = 0;
            foreach ($standbys as $key => $server) {
                if ((int) $key > $max) {
                    $max = $key;
                }
            }
            
            $maxStandby = json_decode($standbys[$max]);
            if ($maxStandby->uuid == self::$uuid) {
                eventManager::triggle(server::STANDBYER_TO_MASTER, [
                    'current-master' => $max,
                    'server' => $data['server']
                ]);
            }
        });
        // 伺服丢失
        eventManager::addCallBack(server::SLAVER_LOST, function ($data) {
            log::write('keepalive', 'slaver is out of service.');
            sync::del(sync::$slaverDir . $data['file']);
            $slaver = apiSlaver::find([
                'uuid' => $data['uuid']
            ]);
            $slaver->_updated = date('Y-m-d H:i:s');
            $slaver->_status = status::_EXIT;
            $slaver->save();

            // 修复活动
            $taskManager = new taskManager(executerFactory::load($slaver->executer));
            $taskManager->fix($slaver->uuid);
            
            eventManager::triggle(server::SYNC);
        });
        // 备用机器丢失
        eventManager::addCallBack(server::STANDBY_LOST, function ($data) {
            log::write('keepalive', 'standby is out of service.');
            sync::del(sync::$standbyDir . $data['file']);
            $master = apiMaster::find([
                'uuid' => $data['uuid']
            ]);
            $master->_status = status::_EXIT;
            $master->save();
        });
        // 保持伺服存活
        eventManager::addCallBack(server::KEEP_SLAVER_ALIVE, function ($data) {
            if (sync::isStandby())
                return;
            $slavers = sync::loadSlavers();
            foreach ($slavers as $type => $_slavers) {
                foreach ($_slavers as $file => $slaver) {
                    $slaver = json_decode($slaver);
                    $client = new \swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC);
                    $client->on("connect", function ($cli) {
                        $cli->send("\n");
                    });
                    $client->on("receive", function ($cli, $_data) use ($slaver) {
                        log::write('keepalive', 'slaver[' . $slaver->ip . ',' . $slaver->port . '] is alive');
                    });
                    $client->on("error", function ($cli) use ($data, $file, $type, $slaver) {
                        log::write('keepalive', 'slaver[' . $slaver->ip . ',' . $slaver->port . '] is out of service.');
                        eventManager::triggle(server::SLAVER_LOST, array_merge($data, [
                            'file' => $type . "/" . $file,
                            'uuid' => $slaver->uuid
                        ]));
                    });
                    $client->on("close", function ($cli) use ($data) {});
                    $client->connect($slaver->ip, $slaver->port, 0.5);
                }
            }
            unset($data);
            unset($slavers);
        });
        // 保持备用机器存货
        eventManager::addCallBack(server::KEEP_STANDBY_ALIVE, function ($data) {
            
            if (sync::isStandby())
                return;
            $standbys = sync::loadStandbys();
            foreach ($standbys as $file => $standby) {
                $standby = json_decode($standby);
                $client = new \swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC);
                $client->on("connect", function ($cli) {
                    $cli->send("\n");
                });
                $client->on("receive", function ($cli, $_data) use ($standby) {
                    log::write('keepalive', 'standby[' . $standby->ip . ',' . $standby->port . '] is alive');
                });
                $client->on("error", function ($cli) use ($data, $file, $standby) {
                    eventManager::triggle(server::STANDBY_LOST, array_merge($data, [
                        'file' => $file,
                        'uuid' => $standby->uuid
                    ]));
                });
                $client->on("close", function ($cli) use ($data) {});
                $client->connect($standby->ip, $standby->port, 0.5);
            }
            unset($data);
            unset($standby);
        });
        // 保持master存活
        eventManager::addCallBack(server::KEEP_MASTER_ALIVE, function ($data) {
            
            if (! sync::isStandby()) {
                sleep($this->config['keepalive']);
                $data['flag'] = 1;
                eventManager::triggle(server::KEEP_MASTER_ALIVE, $data);
            }
            $client = new \swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC);
            $client->on("connect", function ($cli) {
                $cli->send("\n");
            });
            $client->on("receive", function ($cli, $_data) {
                log::write('keepalive', 'check');
            });
            $client->on("error", function ($cli) use ($data) {
                log::write('keepalive', 'master is out of service.');
                eventManager::triggle(server::MASTER_LOST, $data);
            });
            $client->on("close", function ($cli) use ($data) {
                sleep($this->config['keepalive']);
                $data['flag'] = 1;
                eventManager::triggle(server::KEEP_MASTER_ALIVE, $data);
            });
            
            while (! $masters = sync::loadMaster()) {
                log::write('keepalive', 'no master info.');
                sleep(1);
            }
            
            $master = json_decode($masters['master.info']);
            // 获取主服务器Id
            $client->connect($master->ip, $master->port, 0.5);

            unset($data);
            unset($master);
        });
        // 进程启动 开始出发存活检测
        eventManager::addCallBack(server::WORKER_START, function ($data) {
            if (self::$workerCount->get() < 2)
                self::$workerCount->add(1);
            if (self::$workerCount->get() == 1 || isset($data['flag'])) {
                if (! $this->isMaster()) {
                    eventManager::triggle(server::KEEP_MASTER_ALIVE, $data);
                }
                $server = $data['server'];
                $server->tick($this->config['tick'], function () use ($data) {
                    eventManager::triggle(server::KEEP_SLAVER_ALIVE, $data);
                    eventManager::triggle(server::KEEP_STANDBY_ALIVE, $data);
                });
            }
        });
        // 遍历进程如果是master则服务器启动后就开始轮训
        foreach ($this->processes as $key => $process) {
            if ($this->config['role'] == 'master') {
                eventManager::addCallBack(server::AFTER_START_UP, [
                    $process,
                    'run'
                ]);
            } else {
                eventManager::addCallBack(server::STANDBYER_TO_MASTER, [
                    $process,
                    'run'
                ]);
            }
        }
        // 备用机器注册
        eventManager::addCallBack(server::STANDBY_REGISTER, function ($data) {
            $cmd = $data['cmd'];
            $server = $data['server'];
            while (true) {
                $id = time();
                $fileName = sync::$standbyDir . $id;
                if (! is_file($fileName)) {
                    sync::put($fileName, json_encode([
                        'uuid' => $cmd->_data['uuid'],
                        'ip' => $cmd->_data['ip'],
                        'port' => $cmd->_data['port'],
                        'pid' => $cmd->_data['pid']
                    ]));
                    break;
                }
            }
            eventManager::triggle(server::SYNC, $server);
        });
        // 伺服注册
        eventManager::addCallBack(server::SLAVER_REGISTER, function ($data) {
            try {
                $cmd = $data['cmd'];
                $server = $data['server'];
                apiSlaver::update_all([
                        'set' => [
                            '_status' => status::_EXIT
                        ],
                        'conditions' => [
                            '_status = ? AND executer = ? AND master_uuid <> ?',
                            status::_WORKING,
                            $cmd->_data['executer'],
                            $this->id
                        ]
                    ]);

                apiSlaver::add($cmd->_data['uuid'], $this->id, $cmd->_data['ip'], $cmd->_data['port'], status::_WORKING, date("Y-m-d H:i:s"), date("Y-m-d H:i:s"), $cmd->_data['executer'], $cmd->_data['process-count']);
                
                if (! is_dir(sync::$slaverDir . $cmd->_data['executer']))
                    mkdir(sync::$slaverDir . $cmd->_data['executer']);
                
                sync::put(sync::$slaverDir . $cmd->_data['executer'] . "/" . $cmd->_data['ip'] . '-' . $cmd->_data['port'] . ".info", json_encode([
                    'uuid' => $cmd->_data['uuid'],
                    'ip' => $cmd->_data['ip'],
                    'port' => $cmd->_data['port'],
                    'pid' => $cmd->_data['pid'],
                    'executer' => $cmd->_data['executer']
                ]));
                eventManager::triggle(server::SYNC, $server);
            } catch (\Exception $e) {
                log::write('insert master', 'faile:' . $e->getMessage());
                exit();
            }
        });
        
        $processes = $this->processes;
        eventManager::addCallBack(server::SHUT_DOWN, function ($server) use ($processes) {
            foreach ($processes as $process) {
                $pid = $process->getPid();
                posix_kill($pid, - 15);
                log::write('process exit', 'process ' . $process->getName() . ' [' . $pid . '] exit.');
            }
        });
    }
    // 注册错误
    public function registerErro()
    {
        register_shutdown_function(function () {
            date_default_timezone_set('Etc/GMT-8');
            ini_set('error_log', ROOT . '/runtime/log/' . date('Y-m-d') . '_weblog.txt');
            ini_set('ignore_repeated_errors', 1);
            $user_defined_err = error_get_last();
            $msg = sprintf('%s %s %s %s', date("Y-m-d H:i:s"), $user_defined_err['message'], $user_defined_err['file'], $user_defined_err['line']);
            log::write('error-php', $msg);
            error_log($msg, 0);
        });
        set_error_handler(function ($errno, $errstr, $errfile, $errline) {
            log::write("erro", "no:" . $errno . " str:" . $errstr . " file:" . $errfile . " line:" . $errline, log::ERROR);
        });
    }
    // 获取id
    public function getId()
    {
        return $this->id;
    }
    
    // 启动
    public function startUp()
    {
        log::write('server-startup', 'ready start,bind port:' . $this->config['port']);
        eventManager::triggle(server::START_UP);
    }
    
    // 关闭
    public function shutDown($template)
    {}
    // 暂停
    public function pause()
    {}
    // 设置类型
    public function setType()
    {}
    // 设置handel
    public function setHandle()
    {}
    // 检查
    public function check()
    {}
    // 添加进程
    public function addProcess($process)
    {
        $process->setServer($this);
        $this->processes[$process->getName()] = $process;
    }
    // 同步移除进程
    public function removeProcess($process)
    {}
    // 注册为伺服
    public function registerSlaver(command $cmd, $server)
    {
        if (sync::isLock()) {
            $cmd->_message = 'server is syncing, u can try later';
            $cmd->_success = false;
        } else {
            $cmd->_message = 'slaver register success';
            $cmd->_success = true;
            log::write("register", "remote slaver[id:" . $cmd->_data['executer'] . ",ip:" . $cmd->_data['ip'] . ",port:" . $cmd->_data['port'] . "] register success");
            eventManager::triggle(server::SLAVER_REGISTER, [
                'cmd' => $cmd,
                'server' => $server
            ]);
        }
        return $cmd;
    }
    // 注册为备用机器
    public function registerStandby(command $cmd, $server)
    {
        if (sync::isLock()) {
            $cmd->_message = 'server is syncing, u can try later';
            $cmd->_success = false;
        } else {
            $cmd->_message = 'register standby success';
            $cmd->_success = true;
            eventManager::triggle(server::STANDBY_REGISTER, [
                'server' => $server,
                'cmd' => $cmd
            ]);
        }
        
        return $cmd;
    }
    // 是否是master
    public function isMaster()
    {
        // 阻塞等待
        while (! $master = sync::loadMaster()) {
            sleep(1);
        }
        $master = json_decode($master['master.info']);
        return (self::$uuid == $master->uuid);
    }

    public function listProcess()
    {}

    public function checkProcess()
    {}
    // 同步信息
    public function syncInfo($cmd)
    {
        $newMasterInfo = $cmd->_data['master']['master.info'];
        $oldMaster = sync::loadMaster();
        if (! isset($oldMaster['master.info'])) {
            $oldMaster['master.info'] = '';
        }
        
        if ($newMasterInfo != $oldMaster['master.info']) {
            log::write('master changed', 'master change. old-> ' . $oldMaster['master.info'] . ',' . 'new-> ' . $newMasterInfo);
        }
        
        $this->environment->clearDir(sync::$masterDir);
        $this->environment->clearDir(sync::$slaverDir);
        $this->environment->clearDir(sync::$standbyDir);
        sync::save($cmd->_data);
        $cmd->_success = true;
        $cmd->_message = 'sync standby success';
        return $cmd;
    }
    // 同步
    public function sync()
    {
        sync::start();
    }
}

