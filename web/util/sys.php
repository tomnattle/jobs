<?php
namespace util;
//系统帮助类

class sys
{
    // 获取当前内存
	public static function getMemory($flag = false) {
        if(!$flag){
        	return memory_get_usage();
        } 
        $size = memory_get_usage();
        $unit = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
        return @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . ' ' . $unit[$i];
    }

}