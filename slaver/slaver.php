<?php
namespace slaver;

use client\clientFactory;
use command\command;
use util\id;
use util\log;

class slaver
{

    private $ip;

    private $port;

    private $client;

    public function __construct($ip, $port)
    {
        $this->ip = $ip;
        $this->port = $port;
    }
    // 分派任务
    public function assignTask($jobId, $task, $batch = null)
    {
        $cmd = new command();
        $cmd->_code = 0;
        $cmd->_data = [
            'id' => $task->id,
            'jobId' => $jobId,
            'uuid' => $task->uuid,
            'projectId' => $task->projectid,
            'main_type' => $task->main_type, 
            'name' => $task->type,
            '_status' => $task->_status,
            'config' => json_encode(array_merge(json_decode($task->config, 1), [
                'batch_uuid' => $batch ? $batch->uuid : 0,
                '_index' => $batch ? $batch->_index : 0
            ]))
        ];
        
        $cmd->_id = id::gen(self::class);
        $cmd->_message = 'assign task';
        $cmd->_name = 'task';
        $cmd->_success = 'true';
        $cmd->_time = date('Y-m-d H:i:s');
        $result = $this->send($cmd->toString());
        return $result;
    }
    // 发送
    public function send($data)
    {
        try {
            $client = clientFactory::create($this->ip, $this->port);
            $client->send($data);
            $data = $client->recv();
            $client->close();
            $cmd = command::purse($data);
            if (! $cmd->_success) {
                throw new \Exception('failure,task id [' . $cmd->_id . ']');
            }
        } catch (\Exception $e) {
            log::write('assign task ', $e->getMessage());
            return false;
        }
        log::write('assign task ', 'server response :' . $cmd->_message);
        return true;
    }
}

