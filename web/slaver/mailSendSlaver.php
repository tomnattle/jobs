<?php
namespace slaver;

use api\slaver as apiSlaver;
use slaver\type;
use api\apiFactory;
use api\job;
use util\log;

class mailSendSlaver extends base
{

    public static function getName()
    {
        return type::MAIL_SEND;
    }

    public function __construct()
    {
        $this->setName(self::getName());
    }
    // 分派任务
    public function assignJob($jobId, $slaver, $task)
    {
        try {
            $batch = $task->batch;
            $task = $task->task;
            
            $job = apiFactory::get(job::class);
            $job->uuid = $jobId;
            $job->type = $task->type;
            $job->task_uuid = $task->uuid;
            $job->slaver_uuid = $slaver->uuid;
            $job->batch_uuid = $batch->uuid;
            $job->status = "processing";
            $job->count = 0;
            $job->_created = date("Y-m-d H:i:s");
            $job->_process_rate = 0;
            $job->save();
            
            $count = $this->updateProcessingJobCount($slaver->uuid);

            log::write('busy_process_count','- slaverid:' . $slaver->uuid . ',jobid: ' . $jobId . ',count:' . $count);

        } catch (\Exception $e) {
            log::write('assignJob', 'erro:' . $e->getMessage(), log::ERROR);
            return false;
        }
    }
}

