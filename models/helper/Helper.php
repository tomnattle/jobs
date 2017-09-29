<?php

namespace models\helper;

class Helper
{
    const ENCRYPT_KEY = 'webpower2017!@#';
    // 创建加密id
    public static function createGuid()
    {
        $charid = strtoupper(md5(uniqid(mt_rand(), true)));
        $hyphen = chr(45);// "-"
        $uuid = substr($charid, 0, 8).$hyphen
        .substr($charid, 8, 4).$hyphen
        .substr($charid,12, 4).$hyphen
        .substr($charid,16, 4).$hyphen
        .substr($charid,20,12);
        return $uuid;
    }
    // 加密
    public static function encrypt($data)
    {
        // print '{{' . self::ENCRYPT_KEY . '}}' . "\n";
        $key    =   md5(self::ENCRYPT_KEY);
        $x      =   0;
        $len    =   strlen($data);
        $l      =   strlen($key);
        $char   =   '';
        $str    =   '';
        for ($i = 0; $i < $len; $i++)
        {
            if ($x == $l)
            {
                $x = 0;
            }
            $char .= $key{$x};
            $x++;
        }
        for ($i = 0; $i < $len; $i++)
        {
            $str .= chr(ord($data{$i}) + (ord($char{$i})) % 256);
        }
        return self::urlsafe_b64encode($str);
    }

    public static function urlsafe_b64encode($string) {
        $data = base64_encode($string);
        $data = str_replace(array('+','/','='),array('-','_',''),$data);
        return $data;
    }

    public static function urlsafe_b64decode($string) {
        $data = str_replace(array('-','_'),array('+','/'),$string);
        $mod4 = strlen($data) % 4;
        if ($mod4) {
            $data .= substr('====', $mod4);
        }
        return base64_decode($data);
    }
}