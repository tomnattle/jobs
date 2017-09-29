<?php
namespace task;

use api\task as apiTask;
use util\log;

class smsSendTask extends base
{

    const NAME = "sms-send";

    const OTHER_NAME = "mms-send";

    public function __construct()
    {
        $this->setName(self::NAME);
    }
    // 修改
    public function fix($slaverUuid = 0)
    {
        log::write('autofix','sms\mms do nothing');
    }
    // 加载准备好的活动
    public function loadReady()
    {
        $option = [
            'conditions' => "type in('" . self::NAME . "' ,'" . self::OTHER_NAME . "' ) AND _status = 'ready'  AND publish_status ='formal'  AND _plan_time<=now()"
        ];
        
        return apiTask::first($option);
    }

    // 预检测
    public function preCheck()
    {
        
        $this->startUpRestartTask();
    }
    // 开始重启活动
    public function startUpRestartTask(){

        $option = [
            'conditions' => "type in('" . self::NAME . "' ,'" . self::OTHER_NAME . "' )  AND _status = 'restart' "
        ];
        $result = apiTask::all($option);
        foreach ($result as $task) {
            
            apiTask::update_all([
                'set' => [
                    '_status' => 'ready'
                ],
                'conditions' => [
                    'uuid' => $task->uuid
                ]
            ]);
            
            log::write('task-begin', 'task [' . $task->id . ',' . $task->uuid . '] restart');
            // 更新批次为ready
        }

    }
}


