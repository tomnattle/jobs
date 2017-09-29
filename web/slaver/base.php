<?php
namespace slaver;

use slaver\status;
use api\job;
use util\log;
use api\slaver as apiSlaver;
use api\apiFactory;
use api\task;
use sync\sync;

class base implements Islaver
{

    protected $name;
    // 设置名称
    public function setName($name)
    {
        $this->name = $name;
    }
    // 加载空闲机器
    public function loadFrees($master_uuid)
    {
        $options = [
            'conditions' => "executer = '" . $this->name . "' AND master_uuid = '" . $master_uuid . "' AND process_count > busy_process_count AND _status = 'working' order by busy_process_count asc"
        ];
        $result = apiSlaver::find('all', $options);
       
        return $result;
    }
    // 发布任务
    public function assignJob($jobId, $slaver, $task)
    {
        try {
            $job = apiFactory::get(job::class);
            $job->uuid = $jobId;
            $job->type = $task->type;
            $job->task_uuid = $task->uuid;
            $job->slaver_uuid = $slaver->uuid;
            //$job->batch_uuid = $batch_uuid;
            $job->status = "processing";
            $job->count = 0;
            $job->_created = date("Y-m-d H:i:s");
            $job->_process_rate = 0;
            $job->save();
            
            //update busy_processing_count
            $count = $this->updateProcessingJobCount($slaver->uuid);

            log::write('busy_process_count','- slaverid:' . $slaver->uuid . ',jobid: ' . $jobId . ',count:' . $count);
        } catch (\Exception $e) {
            log::write('assignJob', 'erro:' . $e->getMessage(), log::ERROR);
            return false;
        }
    }

    // 同步更新进行中的作业数量
    public function updateProcessingJobCount($slaver_uuid)
    {
        $option = [
            'conditions' => "slaver_uuid = '" . $slaver_uuid . "' AND status = 'processing' "
        ];

        $jobs  = job::all($option);
        $count = count($jobs);
        apiSlaver::update_all([
                'set' => [
                    'busy_process_count' => count($jobs)
                ],
                'conditions' => [
                    'uuid' => $slaver_uuid
                ]
        ]);

        return $count;
    } 
    // 完成作业
    public function finishJob($jobId, $_status, $abort_reason)
    {
        try{
            $job = job::find([
                'uuid' => $jobId
            ]);
            
            $job->status = $_status;
            $job->_process_rate = 100;
            $job->_finished = date('Y-m-d H:i:s');
            $job->abort_reason = $abort_reason;
            $job->save();
            
            $count = $this->updateProcessingJobCount($job->slaver_uuid);
            log::write('busy_process_count','- slaverid:' . $job->slaver_uuid . ',jobid: ' . $jobId . ',count:' . $count);
        }catch(\Exception $e){
            log::write('finishJob', 'erro:' . $e->getMessage(), log::ERROR);
        }
    }
}