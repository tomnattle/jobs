<?php
namespace util;

class id
{
    // 产生uuid
    public static function gen($namespace)
    {
        static $guid = '';
        $uid = uniqid("", true);
        $hash = strtoupper(hash('ripemd128', $uid . $guid . md5($namespace)));
        $guid = substr($hash, 0, 8) . '-' . substr($hash, 8, 4) . '-' . substr($hash, 12, 4) . '-' . substr($hash, 16, 4) . '-' . substr($hash, 20, 12);
        return $guid;
    }
}

