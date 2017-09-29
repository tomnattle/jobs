<?php

namespace executer;

use util\configManager;
use util\log;
use models\TaskLogManager;
use api\task;
use event\eventManager;
use schedule\taskFinish;


class base implements Iexecuter {

    // 名称
    static $name;
    // 任务管理器
    protected $taskManager = null;
    // 伺服管理器
    protected $slaverManager = null;
    // 活动日志帮助类
    protected $taskLogModel = null;
    // 活动model
    protected $task = null;
    // 配置信息
    protected $config = [];
    // 系统配置
    protected $sysConfig = [];
    // 代理
    protected $prox = null;
    // 任务uuid
    protected $task_uuid;
    // 项目id
    protected $projectId;
    // 活动id
    protected $task_id;
    // 作业uuid
    protected $job_uuid;
    // 服务器id
    protected $server_id;
    // 批次id
    protected $batch_uuid;
    // 成功数据
    protected $result;
    // 中止数据
    protected $abort;
    // 设置结果
    public function setResult($result) {
        $this->result = $result;
    }
    // 获取处理进度
    public function getProcessRate($key) {
        return $this->processRate[$key];
    }
    // 配置设置
    public function config($data) {
        $this->config = $data;
        //log::write('receive-data', json_encode($data));
        $this->config->_data['config'] = json_decode($this->config->_data['config'], 1);
        $this->server_id = $this->prox->getServer()->getId();

        $this->projectId = $this->config->_data['projectId'];
        $this->task_uuid = $this->config->_data['uuid'];
        $this->batch_uuid = $this->config->_data['config']['batch_uuid'];
        $this->job_uuid = $this->config->_data['jobId'];
        $this->task_id = $this->config->_data['id'];
        $this->task = task::find($this->task_id);
        $this->taskLogModel = new TaskLogManager($this->projectId, $this->task_id);
    }
    // 加载系统配置
    public function loadSysConfig() {
        $this->sysConfig = configManager::loadConfig(self::$name);
    }

    public function getName() {
        return self::$name;
    }

    public function setAbort($abort) {
        $this->abort .= $abort;
    }

    public function setTaskManager($taskManager) {
        $this->taskManager = $taskManager;
    }

    public function setSlaverManafer($slaverManager) {
        $this->slaverManager = $slaverManager;
    }

    public function getTaskManager() {
        return $this->taskManager;
    }

    public function getSlaverManager() {
        return $this->slaverManager;
    }
    // 更新进度
    public function updateProcessRate($num) {
        log::write('process-rate', 'task[' . $this->task_uuid . '] rate[' . $num . ']');
        $this->getTaskManager()->updateProcessRate($this->config->_data['uuid'], $num);
    }

    public function setProx($prox) {
        $this->prox = $prox;
    }

    public function beforeRun() {
        
    }

    public function run() {
        
    }

    public function afterRun() {
        
    }

    // 中断时操作
    public function beforeAbort(){

    }
    // 中断任务
    public function abort() {
        $data = json_encode($this->abort);
        log::write('abort', "task[" . $this->task_id . "] " . $data);
        $this->taskManager->abort($this->task_uuid, $data, $this->batch_uuid);
        
        // 对客户隐身
        //$this->taskLogModel->addLog(TaskLogManager::ACT_ABORT, []);
        
        $this->getSlaverManager()->finishJob($this->job_uuid, 'abort', $data);
        // 把message写回task表abort_reason字段，并设置_status字段为abort。
    }
    // 保存结果
    public function saveResult() {
        $data = json_encode($this->result);
        log::write('save',  "task[" . $this->task_id . "] " .$data);
        $this->taskManager->saveResult($this->task_uuid, $data);
        $this->taskManager->finished($this->task_uuid, $this->batch_uuid);
        $this->getSlaverManager()->finishJob($this->job_uuid, 'finished', '');
        eventManager::triggle(taskFinish::NAME, [
                'task' => $this->task,
            ]);
    }

}
