<?php
namespace util;

class arrayHelper{
	// 判断配置 $config['a.b.c'] 这样的配置
	public static function hasKey($array, $key){
		$keys = explode('.', $key);
		$count = count($keys);

		$result = false;
		if($count > 1){
			switch ($count) {
				case 2:
					$result = isset($array[$keys[0]][$keys[1]]);
					break;
				case 3:
					$result = isset($array[$keys[0]][$keys[1]][$keys[2]]);
					break;
				default:
					throw new \Exception("support max depth for 3 level", 1);
					break;
			}
			return $result;		
		}else{
			return isset($array[$key]);
		}
	}
	// 获取值
	public static function getValue($array, $key){

		$keys = explode('.', $key);
		$count = count($keys);

		$result = '';
		if($count > 1){
			switch ($count) {
				case 2:
					$result = ($array[$keys[0]][$keys[1]]);
					break;
				case 3:
					$result = ($array[$keys[0]][$keys[1]][$keys[2]]);
					break;
				default:
					throw new \Exception("support max depth for 3 level", 1);
					break;
			}
			return $result;		
		}else{
			return ($array[$key]);
		}

	}
}
