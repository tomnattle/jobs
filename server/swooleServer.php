<?php
namespace server;

use server\event\server;
use event\eventManager;
use util\log;
use command\command;

class swooleServer
{
    // 空消息
    const EMPTY_MESSAGE = "\n";
    // 创建
    public static function create($handle)
    {
        // 创建服务器
        $server = new \swoole_server($handle->config['ip'], $handle->config['port'], SWOOLE_BASE, SWOOLE_SOCK_TCP);
        $server->set($handle->config['set']);
        $server->on('Connect', function ($serv, $fd) {
            // 检查来源 如果ip不再白名单 拒绝请求
        });
        // 接受信息 后回调
        $server->on('Receive', function ($server, $fd, $from_id, $data) use ($handle) {
            if ($data == self::EMPTY_MESSAGE) {
                // 返回空
                $server->send($fd, $data == self::EMPTY_MESSAGE);
            } else {
                // 转换信息为名利对象
                $cmd = command::purse($data);
                if (! $cmd) {
                    $cmd = command::create('server-response', [], command::CODE_EMPTY_PARAM, false, 'param not valid');
                } else {
                    if (! isset($handle->config['supportCmds'][$cmd->_name])) {
                        // 处理命令
                        $cmd = $handle->{$cmd->_name}($cmd, $server);
                    } else {
                        $cmd->err(command::CODE_UNKNOW_NAME, 'unsupport command');
                    }
                }
                // 返回结果
                $server->send($fd, $cmd->toString());
            }
            $server->close($fd);
        });
        
        $server->on('start', function ($server) {
            log::write('server-startup', 'server[' . $server->master_pid . '] has started');
            log::write(".", log::repeat("="));
            sleep(1);
            // 触发启动后事件
            eventManager::triggle(server::AFTER_START_UP, $server);
        });
        // 触发进程开启
        $server->on('WorkerStart', function ($serv, $worker_id) {
            // var_dump(get_included_files());
            if ($serv->setting['worker_num'] > $worker_id) {
                eventManager::triggle(server::WORKER_START, [
                    'server' => $serv
                ]);
            }
        });
        
        $server->on('close', function ($server, $fd, $from_id) {
            // do nothing
        });
        // 任务派发
        $server->on('task', function ($serv, $task_id, $from_id, $data) use ($handle) {
            $cmd = $handle->onTask($data);
            $serv->finish($cmd);
        });
        
        $server->on('finish', function ($serv, $task_id, $data) {
            log::write('task-finish', 'ok');
        });
        
        $server->on('shutdown', function ($server) {
            log::write('server-shutdown', 'server is shutdowning.');
            eventManager::triggle(server::SHUT_DOWN);
        });
        // 触发start事件
        eventManager::addCallBack(server::START_UP, [
            $server,
            'start'
        ]);
        
        return $server;
    }
}

