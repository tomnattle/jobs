<?php

namespace util;

use util\log;

class dir {
    // 创建目录名称
    public static function mkDirName($path) {
        try {
            //生成文件名
            //log::write('space-debug', json_encode($path));
            $destination = $path;
            // 生成csv
            if (!dirname($destination)) {
                mkdir(dirname($destination));
            }
            return $destination;
        } catch (\Exception $ex) {
            throw new \Exception('mdkir-error', 'error info: ' . $ex->getMessage());
        }
    }
    // 创建目录
    public static function mkDir($path) {
        try {
            //生成文件名
            //log::write('space-debug', json_encode($path));
            $destination = ROOT . '/' . $path;
            if (!is_dir($destination)) {
                mkdir($destination);
            }
            return $destination;
        } catch (\Exception $ex) {
            throw new \Exception('mdkir-error', 'error info: ' . $ex->getMessage());
        }
    }

}
