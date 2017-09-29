<?php
namespace server;

use executer\prox as executerProx;
use environment\prox as environmentProx;
use util\log;
use util\configManager;
use event\eventManager;
use server\event\server;
use client\clientFactory;
use command\command;
use util\id;
use ActiveRecord\Config;
use sync\sync;

class slaver implements Islaver
{

    public $config;

    static $workerCount = null;

    private $id;

    private $executerProx;

    private $environment;

    public function __construct()
    {
        self::$workerCount = new \swoole_atomic(0);
        $this->id = id::gen(self::class);
    }
    
    // 初始化
    public function init()
    {
        $this->registerErro();
        $this->executerProx = new executerProx($this);
        $this->environment = new environmentProx($this);
        sync::config($this->environment);
        $this->loadConfig();
        $this->initServer();
        $this->initDd();
        $this->initCallBack();
    }
    
    // loadconfig
    public function loadConfig()
    {
        $this->config = configManager::loadConfig("client");
    }

    public function initServer()
    {
        $this->server = swooleServer::create($this);
    }

    public function initDd()
    {
        $cfg = Config::instance();
        $cfg->set_model_directory(ROOT . "/api");
        $cfg->set_connections(\util\configManager::loadSysConfig("conn"));
        Config::initialize(function ($cfg) {
            $cfg->set_default_connection(ENV);
        });
    }

    public function initCallBack()
    {

        

        eventManager::addCallBack(server::AFTER_START_UP, function ($server) {
            sync::put(sync::$node_root . 'master.pid', $server->master_pid);
        });
        
        eventManager::addCallBack(server::AFTER_START_UP, function ($server) {
            $this->register($server->master_pid);
        });
        
        eventManager::addCallBack(server::MASTER_LOST, function () {
            log::write('keepalive', 'master is out of service.');
        });
        
        eventManager::addCallBack(server::KEEP_MASTER_ALIVE, function ($data) {
            $client = new \swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC); // 异步非阻塞
            
            $client->on("connect", function ($cli) {
                $cli->send("\n");
            });
            
            $client->on("receive", function ($cli, $data) {
                log::write('keepalive', 'check');
            });
            
            $client->on("error", function ($cli) use ($data) {
                eventManager::triggle(server::MASTER_LOST);
                if (! isset($data['sleep']) || $data['sleep'] > 30 || $data['sleep'] <= 0)
                    $data['sleep'] = 1;
                sleep($data['sleep']);
                $data['sleep'] += $data['sleep'];
                eventManager::triggle(server::KEEP_MASTER_ALIVE, $data);
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
            $client->connect($master->ip, $master->port, 0.5);
        });
        
        eventManager::addCallBack(server::WORKER_START, function ($data) {
            if (self::$workerCount->get() < 2)
                self::$workerCount->add(1);
            if (self::$workerCount->get() == 1 || isset($data['flag'])) {
                eventManager::triggle(server::KEEP_MASTER_ALIVE, $data);
            }
        });

        // 加载时间通知
        $eventsScheduleConfig = configManager::loadSysConfig("events");

        foreach($eventsScheduleConfig['events'] as $className)
        {
            eventManager::addEvent(new $className());
        }
    }

    
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

    public function getId()
    {
        return $this->id;
    }
    // 启动
    public function startUp()
    {
        log::write('server-startup', 'ready start');
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
    // 异步任务
    public function task($cmd, $server)
    {
        $server->task($cmd);
        log::write('new task', 'accept task[' . $cmd->_data['uuid'] . ']');
        $cmd->_code = 200;
        $cmd->_message = 'accept task[' . $cmd->_data['uuid'] . ']';
        $cmd->_name = 'task';
        $cmd->_success = 'true';
        $cmd->_time = date('Y-m-d H:i:s');
        return $cmd;
    }

    public function onTask($cmd)
    {
        $this->executerProx->execute($cmd);
    }

    public function loadExecuter($executer)
    {
        $this->executerProx->setExecuter($executer);
    }
    // 同步信息
    public function syncInfo($cmd)
    {
        $newMasterInfo = $cmd->_data['master']['master.info'];
        $oldMaster = sync::loadMaster();
        if (! isset($oldMaster['master.info'])) {
            $oldMaster['master.info'] = '';
        }
        if ($newMasterInfo != $oldMaster) {
            log::write('master changed', 'master change. old-> ' . $oldMaster['master.info'] . ',' . 'new-> ' . $newMasterInfo);
        }
        $this->environment->clearDir();
        sync::save($cmd->_data);
        $cmd->_success = true;
        $cmd->_message = 'sync standby success';
        return $cmd;
    }
    // 注册
    public function register($master_pid)
    {
        log::write('register', 'send register to master[ip:' . $this->config['master']['ip'] . ',port:' . $this->config['master']['port'] . ']');
        try {
            $client = clientFactory::create($this->config['master']['ip'], $this->config['master']['port']);
            if ($client) {
                $cmd = command::create("registerSlaver", [
                    'pid' => $master_pid,
                    'uuid' => $this->id,
                    'ip' => $this->config['ip'],
                    'port' => $this->config['port'],
                    'executer' => $this->executerProx->getExecuter()->getName(),
                    'process-count' => $this->config['process-count']
                ], - 1, - 1, 'register for a slaver');
                $client->send($cmd->toString());
                $result = command::purse($client->recv());
                if ($result->_success) {
                    log::write('register', $result->_message);
                } else {
                    throw new \Exception('regist fail, ' . $result->_message);
                }
            }
        } catch (\Exception $e) {
            // 服务器退出
            log::write('register standby', 'target server is not valid. ' . $e->getMessage());
            $this->server->shutdown();
            exit();
        }
    }
}

