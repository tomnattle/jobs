<?php

namespace executer;

use slaver\type;
use util\http;
use util\dir;
use util\log;
use util\id;
use util\configManager;
use util\file;
use models\download as helpDownload;

class export extends base {

    private $processRate = [
        'start' => 1,
        'download' => 30,
        'upload' => 99,
        'finish' => 100
    ];
    public $subConfig = [];

    public function __construct() {
        parent::$name = type::CONTACT_EXPORT;
    }

    // 运行前
    public function beforeRun() {
        $this->subConfig = configManager::loadConfig('params');
        $this->updateProcessRate($this->processRate['start']);
    }

    // 运行
    public function run() {
        $config = $this->config->_data['config'];
        //log::write('space-debug', json_encode($config));
        $csvHeads = $config['csv_head'];
        $list_ids = $config['list'];

        //生成文件
        $destinationF = dir::mkDir($this->sysConfig['file_path']($this->projectId, $this->task_uuid));
        $destination = dir::mkDirName($destinationF . id::gen(self::class) . ".csv");
        $destinations[] = $destination;
        //打开流媒体
        $fp = fopen($destination, 'w');
        fputcsv($fp, $csvHeads);

        // 分批取数据。
        $pageSize = 4000;
        $page = 0;
        $resultUserCount = 0;
        $csvNum = 0;
        while (true) {
            try {
                $uri = $this->sysConfig['import_api']($this->projectId) . "?list_id=" . $list_ids . "&page=" . ($page + 1) . "&page_size=" . $pageSize . "&show_fields=1";
                $res = http::toGet($uri);
                $resultUser = $res->users;
            } catch (\Exception $ex) {
                throw new \Exception('get-data-error', 'error info: ' . $ex->getMessage());
            }
            log::write('space-debug', 'data count: ' . count($resultUser));
            $resultUserCount += count($resultUser);

            if (!$resultUser || count($resultUser) == 0) {
                break;
            }
            //log::write("space-debug", var_dump(($resultUserCount - ($csvNum * $this->subConfig['csv_row_count']))));
            if (($resultUserCount - ($csvNum * $this->subConfig['csv_row_count'])) > $this->subConfig['csv_row_count']) {
                //关闭流媒体
                fclose($fp);
                //生成文件
                $destinationF = dir::mkDir($this->sysConfig['file_path']($this->projectId, $this->task_uuid));
                $destination = dir::mkDirName($destinationF . id::gen(self::class) . ".csv");
                $destinations[] = $destination;
                //打开流媒体
                $fp = fopen($destination, 'w');
                fputcsv($fp, $csvHeads);
                $csvNum++;
            }

            foreach ($resultUser as $record) {
                $oss = (array) $record;
                foreach ($csvHeads as $v) {
                    //过滤需要的字段
                    $os[$v] = $oss[$v];
                }
                //存入数据
                fputcsv($fp, $os);
            }
            $page ++;
        }

        //关闭流媒体
        fclose($fp);
        $filesize = file::getSize($destinationF);

        // 上传csv
        $uri = $this->sysConfig['upload_uri']($this->subConfig['token']);

        $data = [
            'projectId' => $this->projectId,
            'row-count' => 5,
            'encode' => 'utf8',
        ];
        foreach ($destinations as $destination) {

            $response = http::toPost($uri, $data, array('upload_file' => $destination));

            log::write('space-debug', json_encode($response));
            if ($response) {
                $responseData = $response->urlOut;
                unlink($destination);
                $return['csv_url'][] = $this->sysConfig['downloader_uri']($this->projectId, $responseData);
            }
        }

        //unlink($destination);
        //throw new \Exception("upload response error.", 1302601);

        helpDownload::saveResult($this->sysConfig['download'], $this->projectId, $this->config->_data['config']['downloadId'], $return['csv_url'], $filesize);

        unlink($destinationF);
        $return['users-count'] = $resultUserCount;
        //返回值记录到taskdb中
        log::write('space-result', json_encode($return));
        $this->result = $return;
        $this->updateProcessRate($this->processRate['finish']);
    }

    // 运行后
    public function afterRun() {
        $this->updateProcessRate($this->processRate['finish']);
    }

    // 保存结果
    public function saveResult() {
        parent::saveResult();
    }

}
