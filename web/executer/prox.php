<?php
namespace executer;

use util\log;
use task\taskManager;
use slaver\slaverManager;

class prox
{

    private $executer;

    private $server;

    public function __construct($server)
    {
        $this->server = $server;
    }

    public function setExecuter($executer)
    {
        $this->executer = $executer;
    }

    public function getExecuter()
    {
        return $this->executer;
    }

    public function getServer()
    {
        return $this->server;
    }
    
    // 运行整个executer
    public function execute($data)
    {   
        $this->executer->setProx($this);
        $this->executer->config($data);
        $this->executer->loadSysConfig();
        $this->executer->setTaskManager(new taskManager($this->executer));
        $this->executer->setSlaverManafer(new slaverManager($this->executer));
        try {
            // 运行前脚本
            log::write($this->executer->getName(), 'beforeRun');
            $this->executer->beforeRun();
            // 执行处理脚本
            log::write($this->executer->getName(), 'Run');
            $this->executer->run();
            // 运行后脚本
            log::write($this->executer->getName(), 'afterRun');
            $this->executer->afterRun();
            // 保存结果
            log::write($this->executer->getName(), 'saveResult');
            $this->executer->saveResult();
        } catch (\Exception $e) {
            // 设置中断信息
            $this->executer->setAbort($e->getMessage());
            //print_r($e);
            //log::write('abort', 'abort:' . $e->getMessage());
            //追加出错信息
            try{
                // 调用出错前处理方案脚本
                $this->executer->beforeAbort();  
            }catch(\Exception $e){
                log::write('before-abort','erro ' . $e->getMessage());
                $this->executer->setAbort($e->getMessage());
            }
            // 之行终止接口
            $this->executer->abort();
        }
    }
}

