<?php
namespace task;

use api\task as apiTask;
use api\job as apiJob;
use util\log;

class mailSplitTask extends base
{

    const NAME = "mail-split";

    const NICK_NAME = "mail-send";

    const SMS_SEND = "sms-send";

    const MMS_SEND = "mms-send";


    public function __construct()
    {
        $this->setName(self::NAME);
        $this->setName(self::NICK_NAME);
    }
    // 修正伺服管理 但是状态依然时进行中的任务
    public function fix($slaverUuid = 0)
    {
        if($slaverUuid)
        {
            $option = [
                'conditions' => "slaver_uuid = '" . $slaverUuid . "' AND status = 'processing' "
            ];
            
            $jobs = apiJob::all($option);
            foreach ($jobs as $key => $job) {
                log::write('autofix', $this->name .', slaver[' . $slaverUuid . '],job[' . $job->uuid . '],task[' . $job->task_uuid . '] _status processing to ready');
                apiTask::update_all([
                        'set' => [
                            '_process_rate' => 0,
                            '_status' => 'pending',
                            'pre_status' => 'ready',
                        ],
                        'conditions' => [
                            'uuid' => $job->task_uuid,
                            '_status' => 'pending',
                            'pre_status' => 'processing'
                        ]
                ]);

                $job->_finished = date('Y-m-d H:i:s');
                $job->status = 'abort';
                $job->abort_reason = 'slaver abort, manual Recycling ';
                $job->save();
            }

        }else{
            $option = [
            'conditions' => "type in ('" . implode("','", [self::NICK_NAME, self::SMS_SEND, self::MMS_SEND]) . "')  AND _status = 'pending' AND pre_status = 'processing' "
            ];

            $tasks = apiTask::all($option);
            foreach ($tasks as $task) {
               $task->pre_status = 'ready';
               $task->save();
               log::write('autofix', self::NAME .', task[' . $task->uuid . '] pre_status processing to ready');
            }
        }
        
    }
    // 加载一个准备好的任务
    public function loadReady()
    {
        $option = [
            'conditions' => "type in ('" . implode("','", [self::NICK_NAME, self::SMS_SEND, self::MMS_SEND]) . "') AND _status = 'pending' AND pre_status = 'ready' "
        ];
        
        return apiTask::first($option);
    }
    // 开始处理状态更新
    public function beginProcess($uuid)
    {
        $task = apiTask::find([
            'uuid' => $uuid
        ]);
        $task->pre_status = 'processing';
        $task->save();
    }
    // 结束任务 状态更新
    public function finished($uuid)
    {
        $task = apiTask::find([
            'uuid' => $uuid
        ]);
        $task->pre_status = 'finished';
        $task->_status = 'ready';
        $task->save();
    }
}


