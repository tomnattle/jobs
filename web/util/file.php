<?php

namespace util;

use League\Csv\Reader;
use League\Csv\Writer;
use util\log;

class file {
    // 文件转换为json
    public static function toJson($file, $folder, $rule) {
        log::write('file-tojson', 'enter');
        log::write('make-json', 'make file[' . $file . '] to folder[' . $folder . ']');
        try {
            log::write('make-json', json_encode($rule));

            $index = 0;
            $limit = $rule['package-limit'];
            $fields = $rule['fields'];
            $character_set = $rule['character_set'];
            $skip = (int) $rule['skip'];
            $csv_head = $rule['csv_head'];

            if (!is_dir($folder))
                mkdir($folder);

            $csv = Reader::createFromPath($file);
            while (true) {
                $rows = $csv->setOffset($skip)
                        ->setLimit($limit)
                        ->fetchAll();

                $count = count($rows);
                if (!$count)
                    break;
                $_rows = [];
                foreach ($rows as $key => $row) {
                    $_row = [];
                    foreach ($csv_head as $_key => $field) {
                        if (strstr($field, '#ignore')) {
                            continue;
                        }
                        if (!isset($row[$_key])) {
                            $row[$_key] = null;
                        } else {
// 检查字段类型
                            if ($character_set != 'utf-8') {
                                try {
                                    $row[$_key] = iconv(strtoupper($rule['character_set']), "UTF-8//IGNORE", $row[$_key]);
                                } catch (\Exception $ex) {
                                    $row[$_key] = $row[$_key];
                                }
                            }
                        }
                        $_row[$field] = $row[$_key];
                    }
                    if ((!empty($_row['email']) && filter_var($_row['email'], FILTER_VALIDATE_EMAIL)) || !empty($_row['mobile'])) {
                        $_rows[] = $_row;
                    }
                }
                if (!empty($_rows)) {
                    file_put_contents($folder . $index . ".json", json_encode($_rows));
                    log::write('make-json', 'doing make file [' . json_encode($rule) . '] to folder[' . $folder . $index . ".json" . ']');
//log::write('check-memory', 'memory used: ' . memory_get_usage());
                    $index ++;
                    $skip += $limit;
                }
            }
        } catch (\Exception $ex) {
            throw new \Exception('make-json', $ex->getMessage());
        }
    }
    // 上传到指定的网址
    public static function toHttp($folder, $uri, $config, $executer, $executerStart) {
        log::write('upload', 'upload $folder[' . $folder . '] to uri[' . $uri . ']');
        $dir = opendir($folder);
        $return = ['total_count' => 0, 'success_count' => 0, 'failure_count' => 0];
        while ($file = readdir($dir)) {
            if ($file != "." && $file != "..") {
                $data = array_merge($config, [
                    'users' => json_decode(file_get_contents($folder . $file))
                ]);
                $start = time();
                $request = \Httpful\Request::post($uri)->body(json_encode($data))->sendsJson();
                $response = mutiTry::maketryPostBody('request ' . $uri, [
                            $request,
                            'send'
                ]);
                $executer->updateProcessRate($executerStart);
//log::write('space-debug', json_encode($data));
                if ($response->body->success) {
                    log::write('send-to-api', 'time ' . (time() - $start) . ',file ' . $file . ' send to ' . $uri);
//log::write('send-to-api', 'success info: ' . json_encode($response->body->data) . ". ");
                    log::write('send-to-api', 'total_count: ' . $response->body->data->result->total_count . '. success_count: ' . $response->body->data->result->success_count . '. failure_count: ' . $response->body->data->result->failure_count . ". ");
                    $return['total_count'] += $response->body->data->result->total_count;
                    $return['success_count'] += $response->body->data->result->success_count;
                    $return['failure_count'] += $response->body->data->result->failure_count;
                } else {
                    log::write('send-to-api', 'erro, file ' . $file . ' send to ' . $uri . ". ");
                    log::write('send-to-api', 'erro, info: ' . json_encode($response->body) . ". ");
                    return false;
                }
                if ($executerStart < 99) {
                    $executerStart++;
                }
            }
        }
        return $return;
    }

    public static function getSize($dirName) {
        $dirsize = 0;
        $dir = opendir($dirName);
        while ($fileName = readdir($dir)) {
            $file = $dirName . "/" . $fileName;
            if ($fileName != "." && $fileName != "..") {
                if (!is_dir($file)) {
                    $dirsize += filesize($file);
                }
            }
        }
        closedir($dir);
        return $dirsize;
    }

    public static function toJsonSigle($rows, $rule) {
        try {
            $_rows = [];
            foreach ($rows as $row) {
                $_row = [];
                foreach ($rule['csv_head'] as $_key => $field) {
                    if (strstr($field, '#ignore')) {
                        continue;
                    }
                    if (!isset($row[$_key])) {
                        $row[$_key] = null;
                    } else {
// 检查字段类型
                        $characterSet = self::detectEncoding($row[$_key]);
                        if ($characterSet != 'UTF-8') {
                            try {
                                $row[$_key] = iconv(strtoupper($characterSet), "utf-8//IGNORE", $row[$_key]);
                            } catch (\Exception $ex) {
                                $row[$_key] = $row[$_key];
                            }
                        }
                    }
                    $_row[$field] = $row[$_key];
                }
                if ((!empty($_row['email']) && filter_var($_row['email'], FILTER_VALIDATE_EMAIL)) || !empty($_row['mobile'])) {
                    $_rows[] = $_row;
                }
                unset($_row);
            }
            if (!empty($_rows)) {
                return $_rows;
            }
            unset($_rows);
            return false;
//throw new \Exception('make-json', "data error, error data: " . json_encode($rows));
        } catch (\Exception $ex) {
            throw new \Exception('make-json', $ex->getMessage());
        }
    }

    public static function toHttpSigle($_rows, $uri, $config) {
        try {

            log::write('upload', 'upload data to uri[' . $uri . ']');

            $return = ['total_count' => 0, 'success_count' => 0, 'failure_count' => 0];
            $data = array_merge($config, [
                'users' => $_rows
            ]);
            $start = time();
            $request = \Httpful\Request::post($uri)->body(json_encode($data))->sendsJson();
            $response = mutiTry::maketryPostBody('request ' . $uri, [
                        $request,
                        'send'
                            ], 3
            );
//log::write('space-debug', json_encode($data));
            if (!empty($response) && !empty($response->body) && $response->body->success) {
                log::write('send-to-api', 'time ' . (time() - $start) . ',data send to ' . $uri);
//log::write('send-to-api', 'success info: ' . json_encode($response->body->data) . ". ");
                log::write('send-to-api', 'total_count: ' . $response->body->data->result->total_count . '. success_count: ' . $response->body->data->result->success_count . '. failure_count: ' . $response->body->data->result->failure_count . ". ");
                $return['total_count'] = $response->body->data->result->total_count;
                $return['success_count'] = $response->body->data->result->success_count;
                $return['failure_count'] = $response->body->data->result->failure_count;
            } else {
                log::write('send-to-api', 'erro, info: ' . json_encode($response->body) . ". ");
                return false;
            }
            unset($data, $start, $request, $response);
            return $return;
        } catch (\Exception $ex) {
            log::write('send-to-api', 'erro, info: ' . $response->__toString() . ". code: " . $response->code . ". ");
            throw new \Exception('send-to-api', $ex->getMessage());
        }
    }

    public static function getMemory() {
        $size = memory_get_usage();
        //return $size;
        $unit = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
        return @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . ' ' . $unit[$i];
    }

    //判断字符集
    public static function detectEncoding($str) {
        $list = array('UTF-8', 'GBK');
        //$list = array(  'GBK','UTF-8');
        foreach ($list as $item) {
            $tmp = mb_convert_encoding($str, $item, $item);
            if (md5($tmp) == md5($str)) {
                return $item;
            }
        }
        return null;
    }

}
