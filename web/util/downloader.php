<?php
namespace util;

class downloader
{
    // 复制uri 到指定的目录 保存为文件
    public static function httpcopy($uri, $path, $timeout = 0)
    {
        log::write('download', 'load uri[' . $uri . '] to path[' . $path . ']');
        
        if (! is_dir(dirname($path)))
            mkdir(dirname($path));
        
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $uri);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            $temp = curl_exec($ch);
            if (@file_put_contents($path, $temp) && ! curl_error($ch)) {
                return $path;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            throw new \Exception('download falure:' . $e->getMessage());
        }
    }
}

