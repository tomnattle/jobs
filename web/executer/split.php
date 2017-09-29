<?php
namespace executer;

use slaver\type;
use util\configManager;
use util\log;
use models\MailSplitter;
use models\smsSplitter;
use models\TaskLogManager;

class split extends base
{
    private $splitter;

    protected $processRate = [
        'start' => 1,
        'finish' => 30,
    ];

    public $subConfig = [];

    public function __construct()
    {
        parent::$name = type::MAIL_SPLIT;
    }

    // 运行前
    public function beforeRun()
    {
        log::write('get-config', print_r($this->config, true));

        // 读取任务配置
        $configData = $this->config->_data;

        $this->taskLogModel->addLog(TaskLogManager::ACT_PRE_START, []);
        $this->taskLogModel->addMonitorLog(TaskLogManager::MONITOR_ACT_PRE_START,[]);

        if(in_array($this->config->_data['name'],['sms-send','mms-send'])){
            $this->splitter = new smsSplitter($configData, $this);
        }elseif($this->config->_data['name']==type::MAIL_SEND){
            $this->splitter = new MailSplitter($configData, $this);
        }else{
            throw new \Exception('unkown type task '.$this->config->_data['name']);
        }
    }

    // 运行
    public function run()
    {
        $this->splitter->run();
    }

    // 运行后
    public function afterRun()
    {
        $this->taskLogModel->addLog(TaskLogManager::ACT_PRE_END, []);
        $this->taskLogModel->addMonitorLog(TaskLogManager::MONITOR_ACT_PRE_FINISHED, [
                'count' => $this->result['contact_total_count'],
                'batch_count' => $this->result['batch_total_count']
            ]);
    }

    public function beforeAbort() {
        $this->splitter->endLock();
    }

    public function abort(){
        parent::abort();

        $this->taskLogModel->addMonitorLog(TaskLogManager::MONITOR_ACT_PRE_ABORT, [
            'abort_reason' => json_encode($this->abort),
            'status' => 'err',
            'task_id' => $this->task_id,
            'append-message' => '提示：可进行 <span url="/admin/task/{$task_id}/retry" class="logActions" >重试</span> 操作'
        ]);
    }

    // 保存结果
    public function saveResult()
    {
        parent::saveResult();
    }
}
