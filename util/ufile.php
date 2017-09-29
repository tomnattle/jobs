<?php
namespace util;

class ufile
{
    // 上传目录到ufile上
    public static function uploadDir($dirname){
    	log::write('upload-dir',$dirname);
        if(is_dir($dirname)){
            $dir = opendir($dirname);
            $uris = [];
            while ($file = readdir($dir)) { 
	            if ($file != "." && $file != "..") {
	                if(is_file($dirname . $file)){
	                    try{
	                        $uris[] =self::up($dirname . $file);
	                    }catch(\Exception $e){
	                        log::write('toUfile-failure','error info' . $e->getMessage());
	                        continue;
	                    }
	                }
	            }
	        }
            log::write('upload-dir','dir upload success:' . $dirname);
            return $uris;
        }
        return [];
    }
    // 上传单个文件
    public static function up($filePath){
    	
        $uri = configManager::loadSysConfig('ufile')[ENV]['upload-post'];
        log::write('upload-ufile','upload :'.$filePath . ',uri: ' . $uri);
        $response = \Httpful\Request::post($uri)
            ->addHeader('Accept', 'application/json')
            ->sendTypes(\Httpful\Mime::FORM)
            ->body(['private' => 1])
            ->attach([
                'upload_file' => $filePath
                ])
            ->send();
        if(!$response->body->success){
           throw new \Exception("upload file [".$filePath."] failure",$response->body->data->message);  
        } 
        return $response->body->data[0]->url;
    }
}

