<?php
namespace task;

use slaver\type;

class taskManager
{

    private $task = null;
    //  获取一个活动对象
    public function __construct($obj)
    {
        switch ($obj->getName()) {
            case type::CONTACT_IMPORT:
                $this->task = new importTask();
                break;
            case type::CONTACT_EXPORT:
                $this->task = new exportTask();
                break;
            case type::MAIL_SPLIT:
                $this->task = new mailSplitTask();
                break;
            case type::MAIL_SEND:
                $this->task = new mailSendTask();
                break;
            case type::DELETE_COLUMN:
                $this->task = new deleteColumnTask();
                break;
            case type::REPORT_EXPORT:
                $this->task = new exportReportTask();
                break;
            case type::SMS_SEND:
                $this->task = new smsSendTask();
                break;
            default:
                throw new \Exception('unknow type of obj');
        }
    }

    public function fix($slaverUuid = 0 )
    {
        return $this->task->fix($slaverUuid);
    }

    public function preCheck()
    {
        return $this->task->preCheck();
    }

    public function loadReady()
    {
        return $this->task->loadReady();
    }

    public function updateProcessRate($uuid, $num)
    {
        $this->task->updateProcessRate($uuid, $num);
    }

    public function updateBatchProcessRate($uuid, $num)
    {
        $this->task->updateBatchProcessRate($uuid, $num);
    }

    public function updateProcessing($task)
    {
        $this->task->beginProcess($task->uuid);
    }

    public function checkTaskFinished($uuid)
    {
        return $this->task->checkTaskFinished($uuid);
    }

    public function getFinishedBatchRate($uuid)
    {
        return $this->task->getFinishedBatchRate($uuid);
    }

    public function getCountBatch($projectId, $id, $batch_id)
    {
        return $this->task->getCountBatch($projectId, $id, $batch_id);
    }

    public function pauseTask($uuid, $reason)
    {
        return $this->task->pauseTask($uuid, $reason);
    }

    public function finished($uuid)
    {
        $this->task->finished($uuid);
    }

    public function finishedBatch($uuid)
    {
        $this->task->finishedBatch($uuid);
    }

    public function updateBatchPaid($batch_uuid){

        $this->task->updateBatchPaid($batch_uuid);
    }
    
    public function saveResult($uuid, $data)
    {
        $this->task->saveResult($uuid, $data);
    }

    public function abort($uuid, $data, $batch_uuid = 0)
    {
        $this->task->abort($uuid, $data, $batch_uuid);
    }

    public function loadBatchInfoReport($task_uuid)
    {
        return $this->task->loadBatchInfoReport($task_uuid);
    }

}

