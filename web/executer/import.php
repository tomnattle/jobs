<?php

namespace executer;

use ActiveRecord\Config;
use slaver\type;
use util\downloader;
use util\file;
use api\apiFactory;
use api\field;
use util\log;
use models\contacts;
use League\Csv\Reader;
use models\corperate;

class import extends base {

    //private $abort = [];
    private $processRate = [
        'start' => 1,
        'download' => 10,
        'makejson' => 30,
        'upload' => 99,
        'finish' => 100
    ];

    public function __construct() {
        parent::$name = type::CONTACT_IMPORT;
    }

    // 运行前
    public function beforeRun() {
        $this->updateProcessRate($this->processRate['start']);
    }

    // 运行
    public function run() {
        // log::write('space-debug', json_encode($this->config->_data));
        // 下载
        ini_set('memory_limit', '-1');
        try {
            $filname = $this->config->_data['config']['url'];
            $file = ROOT . '/' . $this->sysConfig['file_path']($this->projectId, $this->task_uuid);
            $uri = $this->sysConfig['downloader_uri']('server-side', $this->projectId, $filname);
            log::write('check-memory', 'before download: ' . file::getMemory());
            downloader::httpcopy($uri, $file);
            log::write('check-memory', 'after download: ' . file::getMemory());
            $this->updateProcessRate($this->processRate['download']);
        } catch (\Exception $e) {
            throw new \Exception('download-falure, file:' . $uri);
        }
        // 准备数据
        try {
            $fields = []; //apiFactory::get(field::class)->loadFields($this->projectId);
            $rule = [
                'fields' => $fields,
                'character_set' => $this->config->_data['config']['character_set'],
                'skip' => $this->config->_data['config']['skip'],
                'csv_head' => $this->config->_data['config']['csv_head'],
                'package-limit' => $this->sysConfig['package_limit']
            ];
        } catch (\Exception $e) {
            throw new \Exception('prepare-data, get fields failure:' . $uri);
        }

        // 生成数据 并上传 失败记录文件
        try {
            $folder = ROOT . '/' . $this->sysConfig['json_path']($this->projectId, $this->task_uuid);
            if (!is_dir($folder))
                mkdir($folder);
            log::write($file, $folder);

            $apiUri = $this->sysConfig['import_api']($this->projectId) . "?list_id=" . $this->config->_data['config']['list'];
            $config = [
                'merge' => $this->config->_data['config']['duplicate'],
                'operator_email' => $this->config->_data['config']['operator_email'],
                'operator_name' => $this->config->_data['config']['operator_name'],
                'overwrite' => $this->config->_data['config']['overwrite'],
                'sync_total_count' => true
            ];
            $index = 0;
            $total_count = 0;
            $success_count = 0;
            $failure_count = 0;
            $limit = $rule['package-limit'];
            $skip = (int) $rule['skip'];
            $executerStart = $this->processRate['download'];
            $csv = Reader::createFromPath($file);
            $nbRows = $csv->each(function($row) {
                return true;
            });
            $baseNp = ceil($nbRows / $limit);

            $_result = ['total_count' => 0, 'success_count' => 0, 'failure_count' => 0];
            while (true) {
                $rows = $csv->setOffset($skip)
                        ->setLimit($limit)
                        ->fetchAll();

                $count = count($rows);
                //csv 读完了 跳出去
                if (!$count) {
                    $config['sync_total_count'] = true;
                    file::toHttpSigle([], $apiUri, $config);
                    break;
                }
                //生成数据
                $_rows = file::toJsonSigle($rows, $rule);
                if ($_rows) {
                    //上传
                    $_posted = file::toHttpSigle($_rows, $apiUri, $config);

                    if ($_posted) {
                        //返回值总数累加
                        $total_count += $_posted['total_count'];
                        $success_count += $_posted['success_count'];
                        $failure_count += $_posted['failure_count'];
                        //更新进度
                        $this->updateProcessRate($executerStart);
                        $executerStart = (int) (($index / $baseNp) * ($this->processRate['upload'] - $this->processRate['download'])) + $this->processRate['download'];
                    } else {
                        //失败记录文件
                        file_put_contents($folder . $index . ".json", json_encode($_rows));
                        log::write('make-json', 'made file [' . json_encode($rule) . '] to folder[' . $folder . $index . ".json" . ']');
                        log::write('send-to-api', 'erro, file ' . $folder . $index . ".json" . ' send to ' . $apiUri . ". ");
                        throw new \Exception('send-to-api, erro, file ' . $folder . $index . ".json" . ' send to ' . $apiUri . ". ");
                    }
                    unset($_posted, $_rows, $rows, $count);
                    log::write('check-memory', 'index: ' . $index . '; memory used: ' . file::getMemory() . '; ');
                }
                $index++;
                $skip += $limit;
            }

            $_result['total_count'] = $total_count;
            $_result['success_count'] = $success_count;
            $_result['failure_count'] = $failure_count;
            //返回值总数
            $this->result = $_result;
            $this->updateProcessRate($this->processRate['upload']);
        } catch (\Exception $e) {
            $this->abort = $e->getMessage();
            throw new \Exception('make-json, error info: ' . $e->getMessage());
        }
    }

    // 运行后
    public function afterRun() {
        $this->updateProcessRate($this->processRate['finish']);
    }

    // 保存结果
    public function saveResult() {
        corperate::notification(corperate::AFTER_IMPORT, $this->task->_create_operator_uid, $this->task->name, $this->result['total_count'], $this->config->_data['config']['list']);
        contacts::Unlock($this->projectId, $this->config->_data['config']['list'], $this->task_id);
        parent::saveResult();
    }

    // 处理报错信息
    public function beforeAbort() {
        contacts::Unlock($this->projectId, $this->config->_data['config']['list'], $this->task_id);
        //$this->abort;
    }

}
