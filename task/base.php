<?php
namespace task;

use api\task as apiTask;
use api\batch as apiBatch;
use api\job as apiJob;
use util\log;

class base implements Itask
{

    protected $name;

    public function setName($name)
    {
        $this->name = $name;
    }

    //默认修复所有任务 活着修正制定的伺服id处理中的任务 解决服务器重启后 上次处理未完成的任务
    public function fix($slaverUuid = 0)
    {   
        if($slaverUuid){
            $option = [
                'conditions' => "slaver_uuid = '" . $slaverUuid . "' and status = 'processing' "
            ];
            
            $jobs = apiJob::all($option);
            foreach ($jobs as $key => $job) {
                log::write('autofix', $this->name .', slaver[' . $slaverUuid . '],job[' . $job->uuid . '],task[' . $job->task_uuid . '] _status processing to ready');
                apiTask::update_all([
                        'set' => [
                            '_process_rate' => 0,
                            '_status' => 'ready',
                        ],
                        'conditions' => [
                            'uuid' => $job->task_uuid,
                            '_status' => 'processing'
                        ]
                ]);
                
                $job->_finished = date('Y-m-d H:i:s');
                $job->status = 'abort';
                $job->abort_reason = 'slaver abort, manual Recycling ';
                $job->save();
            }

        }else{
            $option = [
                'conditions' => "type = '" . $this->name . "' AND _status = 'processing' "
            ];
            $tasks = apiTask::all($option);
            foreach ($tasks as $task) {
               $task->_status = 'ready';
               $task->save();
               log::write('autofix', $this->name .', task[' . $task->uuid . '] _status processing to ready');
            }
        }

    }   
    // 加载信息
    public function loadInfo($uuid)
    {
        $task = apiTask::find([
            'uuid' => $uuid
        ]);
        return $task;
    }
    // 加载准备好的活动  
    public function loadReady(  )
    {
        $option = [
            'conditions' => "type = '" . $this->name . "' AND _status = 'ready' "
        ];
        
        return apiTask::first($option);
    }
    // 开始处理
    public function beginProcess($uuid)
    {
        $task = apiTask::find([
            'uuid' => $uuid
        ]);
        $task->_status = 'processing';
        $task->_started = date('Y-m-d H:i:s');
        $task->save();
        unset($task);
    }
    // 更新处理进度
    public function updateProcessRate($uuid, $rate)
    {
        $task = apiTask::find([
            'uuid' => $uuid
        ]);
        $task->_process_rate = $rate;
        $task->save();
    }
    // 处理完成
    public function finished($uuid)
    {
        $task = apiTask::find([
            'uuid' => $uuid
        ]);
        $task->_finished = date('Y-m-d H:i:s');
        $task->_status = 'finished';
        $task->save();
    }
    // 保存任务结果
    public function saveResult($uuid, $result)
    {
        $task = apiTask::find([
            'uuid' => $uuid
        ]);
        $task->result = $result;
        $task->save();
    }
    // 任务终止
    public function abort($uuid, $abort_reason, $batch_uuid = 0)
    {
        $task = apiTask::find([
            'uuid' => $uuid
        ]);

        if(is_array($abort_reason))
            $abort_reason = json_encode($abort_reason);

        $task->_status = 'abort';
        $task->abort_reason = $abort_reason;
        $task->_finished = date('Y-m-d H:i:s');
        $task->save();
    }
}