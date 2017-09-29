<?php
namespace util;

use League\Csv\Reader;
use League\Csv\Writer;
use util\log;
use Httpful\Httpful;
use Httpful\Mime;
use Httpful\Handlers\JsonHandler;
use util\arrayHelper;


class http
{
    // get下载并返回数据
    public static function toGet($uri)
    {
        log::write('get-user', 'select api to uri[' . $uri . ']');
        
        $start = time();
        $request = \Httpful\Request::get($uri);
        $response = mutiTry::maketry('request ' . $uri, [
            $request,
            'send'
        ]);
        // log::write('space-debug', json_encode($request));
        // log::write('space-debug', json_encode($response->body));
        if ($response->body->success) {
            log::write('get-from-api', 'time ' . time() - $start . ', get data from ' . $uri);
            return $response->body->data;
        } else {
            log::write('get-from-api', 'erro, send to ' . $uri . ". ");
            return false;
        }
    }
    // 发送csv到指定的uri上
    public static function toPost($uri, $data = [], $files = [])
    {
        log::write('post-csv', 'post csv file to uri[' . $uri . ']');
        
        $start = time();
        log::write('space-debug', json_encode($files));
        $request = \Httpful\Request::post($uri)->body($data)->attach($files);
        $response = mutiTry::maketry('request ' . $uri, [
            $request,
            'send'
        ]);
        // log::write('space-debug', json_encode($request));
        // log::write('space-debug', json_encode($response->body));
        if ($response->body->success) {
            log::write('post-to-api', 'time ' . time() - $start . ', post file to ' . $uri);
            return $response->body->data;
        } else {
            log::write('post-to-api', 'erro, send to ' . $uri . ". ");
            return false;
        }
    }
    // 保存为csv
    public static function toCsv($container, $headers_columns)
    {   
        //
        // 文件标志
        $file_index = 0;
        $rows_index = 0;
        $flag = $container['params']['main_type'];
        $maxLine = $container['params']['max-line'];
        $dir = $container['dir'];
        $header = array_keys($headers_columns);

        // 创建目录
        if(!is_dir(dirname($dir)))
        {
            log::write('mkdir' ,dirname(dirname($dir)));
            mkdir(dirname($dir));
        }

        if(!is_dir($dir))
        {   
            log::write('mkdir' ,$dir);
            mkdir($dir);
        }
        // 文件句柄
        $fp = null;
        while(true){
            $container['params']['page'] += 1;
            $uri  = $container['url']($container['params']);
            log::write('load-uri', $flag .':'. $uri);
            
            $response = \Httpful\Request::get($uri)->expectsJson()->send();
            
            // 短信和彩信的数据格式
            if(in_array($flag, ['sms','mms'])){
                if (!$response->body->code==0 || !$response->body->data) {
                    log::write('load-data', $flag . ', load failure or not data response');
                    break;
                }
                $data = $response->body->data->list;
            }else{
                //邮件的数据格式
                if (!$response->body->success || !$response->body->data) {
                    log::write('load-data','load failure or not data response');
                    break;
                }
                $data = $response->body->data;
            }

            if(!$data){
                break;
            }

            foreach ($data as $key=>$row) {
                log::write(1,$rows_index.'-'.$maxLine);
                if(($rows_index % $maxLine) == 0){
                    $file_index ++;
                    $file = $dir . $file_index . '-' .microtime(true). '.csv';
                    $fp = fopen($file, 'w');
                    //新建一个文件
                    fputcsv($fp, $header);
                }
                $_row = [];
                foreach ($headers_columns as $header => $column) {
                    $row = json_decode(json_encode($row),true);
                    if(arrayHelper::hasKey($row,$column))
                    {
                        $return = arrayHelper::getValue($row, $column);
                        if(is_array($return))
                        {
                            $_row[] = implode(',', $return);
                        }else{
                            $_row[] = $return;                        
                        }
                    }else{
                        $_row[] = '--';
                    }
                }
                fputcsv($fp, $_row);
                $rows_index ++;
            }
            
        }

        if($fp){
            fclose($fp);    
        }
         
    }
}
