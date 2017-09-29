<?php
namespace process;

use slaver\type;
use util\log;
use util\id;
use models\corperate;
use models\TaskLogManager;
use util\file;

class mailSend extends base
{

    public function getName()
    {
        return type::MAIL_SEND;
    }

    public function process(){

        log::write ( 'p-' . $this->getName (), 'enter process' );
        $this->getTaskManager()->fix();
        log::write('p-' . $this->getName (), 'fixed ');

        while (true) {
            // log::write('memery-user-mail-send', file::getMemory());
            // log::write('p-' . $this->getName(), 'load free slaver');
            $slavers = $this->getSlaverManager()->loadFrees($this->server->getId());

            $count = $slavers ? count($slavers) : "no";
            //log::write('p-' . $this->getName(), $count . ' free slaver found.');

            foreach ($slavers as $slaver) {
                // 检查活动是否可以开启
                $this->getTaskManager()->preCheck();
                //log::write('p-' . $this->getName(), 'load undeal task');
                $task = $this->getTaskManager()->loadReady();

                if (! $task) {
                    break;
                }

                $count = $this->getTaskManager()->getCountBatch($task->task->projectid, $task->task->id, $task->batch->_index);
                if($task->batch->paid_status == 0)
                {
                    $result = corperate::deduct($task->task->_create_operator_uid, $task->task->id, $count);
                    if(corperate::DEDUCT_SUCESS != $result){
                        log::write('deduct-falure','扣费失败，任务[' . $task->task->id . '][' . $task->batch->_index . ']暂停,暂停原因:' . $result);
                        try{
                            $this->getTaskManager()->pauseTask($task->task->uuid , 'deduct-failure');
                            $taskLogModel = new TaskLogManager($task->task->projectid, $task->task->id);
                            $taskLogModel->addLog(TaskLogManager::ACT_PAUSE_UNBLANCE, []);
                            

                            $taskLogModel->addMonitorLog(TaskLogManager::MONITOR_ACT_PAUSE_UNBLANCE, []);
                        }
                        catch(\Exception $e){
                            log::write('pause','任务['.$task->task->id.']暂停遇到异常' . $e->getMessage());
                        }
                        
                        if($result == corperate::LACK_QUANTITY || $result == corperate::LACK_DAY_QUANTITY)
                            corperate::notification($result, $task->task->_create_operator_uid, $task->task->name, 0, '');
                        
                        continue;
                    }else{
                        $this->getTaskManager()->updateBatchPaid($task->batch->uuid);
                        log::write('deduct-success','扣费成功，任务[' . $task->task->id . '][' . $task->batch->_index . ']继续..');    
                    }
                }
                    
               

                log::write('p-' . $this->getName(), 'assign undeal task to slaver');

                $jobId = id::gen(self::class);
                $result = $this->assignJob($jobId, $slaver, $task->task, $task->batch);
                if ($result) {
                    log::write('p-' . $this->getName(), ' update task to processing ');
                    $this->getTaskManager()->updateProcessing($task->batch);
                    log::write('p-' . $this->getName(), ' assign task[' . $task->task->uuid . '] batch[' . $task->batch->_index . '] to slaver[' . $slaver->uuid . ']');
                    $this->getSlaverManager()->assignJob($jobId, $slaver, $task);
                } else {
                    log::write('p-' . $this->getName(), 'fail. assign undeal task to slaver', log::FAILURE);
                }
            }

            sleep(1);
        }
    }
}

