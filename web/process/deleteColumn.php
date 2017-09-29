<?php
namespace process;

use slaver\type;
use util\log;
use util\id;

class deleteColumn extends base
{

    public function getName()
    {
        return type::DELETE_COLUMN;
    }
    // 轮训
    public function process()
    {
        log::write('p-' . $this->getName(), 'enter process');
        while (true) {
            // log::write('p-' . $this->getName(), 'load free slaver');
            $slavers = $this->getSlaverManager()->loadFrees($this->server->getId());
            
            $count = $slavers ? count($slavers) : "no";
            // log::write('p-' . $this->getName(), $count . ' free slaver found.');
            foreach ($slavers as $slaver) {
                // log::write('p-' . $this->getName(), 'load undeal task');
                $task = $this->getTaskManager()->loadReady();
                if (! $task) {
                    log::write('p-' . $this->getName(), 'no task found .');
                    break;
                }
                
                // log::write('p-' . $this->getName(), 'assign undeal task to slaver');
                $jobId = id::gen(self::class);
                $result = $this->assignJob($jobId, $slaver, $task);
                if ($result) {
                    log::write('p-' . $this->getName(), ' update task to processing ');
                    $this->getTaskManager()->updateProcessing($task);
                    log::write('p-' . $this->getName(), ' assign task[' . $task->uuid . '] to slaver[' . $slaver->uuid . ']');
                    $this->getSlaverManager()->assignJob($jobId, $slaver, $task);
                } else {
                    log::write('p-' . $this->getName(), 'fail. assign undeal task to slaver', log::FAILURE);
                }
            }
            
            sleep(1);
        }
    }
}

