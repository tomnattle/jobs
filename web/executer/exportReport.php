<?php
namespace executer;

use slaver\type;
use util\configManager;
use util\http;
use util\file;
use util\ufile;
use models\download;

class exportReport extends base
{

    public $container = [];

    private $processRate = [
        'start' => 1,
        'download' => 10,
        'makefile' => 30,
        'upload' => 99,
        'finish' => 100
    ];

    public function __construct()
    {
        parent::$name = type::REPORT_EXPORT;
    }
    
    // 运行前
    public function beforeRun()
    {
        $this->updateProcessRate($this->processRate['start']);
        $this->container['type'] = $this->config->_data['config']['type'];
        if(!isset($this->sysConfig['urls'][$this->container['type']])){
            throw new \Exception("type [" . $this->container['type'] . "] not found ,config data erro", 1);
        }
        $this->container['requireParams'] = $this->sysConfig['params'][$this->container['type']];

        //$this->container['ufile-post-url'] = $this->sysConfig['ufile']['upload-post'];
        
        $this->container['url'] = $this->sysConfig['urls'][$this->container['type']];
        $this->container['page-size'] = $this->sysConfig['page-size'];
        $this->container['dir'] = $this->sysConfig['file_path']($this->projectId, $this->task_id);
        //参数
        $this->container['params'] = $this->config->_data['config'];
        $this->container['params']['page_size'] = $this->container['page-size'];
        $this->container['params']['page'] = 0;
        $this->container['params']['projectId'] = $this->projectId;
        $this->container['params']['main_type'] = $this->config->_data['main_type'];

        $this->container['params']['uriHandle'] = $this->sysConfig['urls']['download'];
        $this->container['params']['max-line'] = $this->sysConfig['max-line'];

        $this->container['headers-columns'] =  $this->sysConfig['headers-columns'][$this->container['type']];
        
        if(isset($this->container['params']['time-span']) && count($this->container['params']['time-span']) == 2){
            $this->container['params']['date_start'] = $this->container['params']['time-span'][0];
            $this->container['params']['date_end'] = $this->container['params']['time-span'][1];
        }else{
            $this->container['params']['date_start'] = '1970-01-01';
            $this->container['params']['date_end'] = date('Y-m-d');
        }
    }
    
    // 运行
    public function run()
    {
        // 参数
        $params = $this->container['params'];
        // 需要的表头
        $header = $this->container['headers-columns'];

        // 检查配置是否正确
        foreach ($this->container['requireParams'] as $key) {
            if(!isset($params[$key])){
                throw new \Exception("[" . $key . "] not found ,config data erro", 1);
            }
        }

        if('raw-data-export' == $this->container['type']){
            //增加自定义字段
            foreach ($this->container['params']['fields'] as $fld_id => $name) {
                $header[$name] = 'fields.'.$name;
            }

            // 多次循环
            $tasks = $this->config->_data['config']['tasks'];
            foreach ($tasks as $key => $taskId) {
                $this->container['params']['page'] = 0;
                $this->container['params']['taskId'] = $taskId;
                http::toCsv($this->container, $header);
            }
        }else{
            // 单次循环讲接口内容写入到csv中
            http::toCsv($this->container, $header);
        }
        
        //上传csv文件到ucloud
        $files = ufile::uploadDir($this->container['dir']);
        //更新download接口  
        download::saveResult($this->container['params']['uriHandle'], $this->container['params']['projectId'], $this->container['params']['downloadId'], $files,file::getSize($this->container['dir'])) ;
        //保存结果
        $this->result = [
            'files' => $files
        ];
    }
    
    // 运行后
    public function afterRun()
    {
        $this->updateProcessRate($this->processRate['finish']);
    }
    
    // 保存结果
    public function saveResult()
    {
        parent::saveResult();
    }
}
