<?php

namespace util;

class mutiTry {
    // 重试
    public static function maketry($name, $handel, $time = 0) {
        $result = null;
        if ($time == 0) {
            $time = 9999;
        }
        while ($time) {
            try {
                $result = call_user_func($handel);
                break;
            } catch (\Exception $e) {
                log::write('make-try', $name . ' try ' . $time . ' times');
                sleep(1);
            }
            $time --;
        }
        return $result;
    }
    // 重新post
    public static function maketryPostBody($name, $handel, $time = 0) {
        $result = null;
        if ($time == 0) {
            $time = 9999;
        }
        while ($time) {
            try {
                $result = call_user_func($handel);
                if (preg_match("/504 Gateway Time-out/", json_encode($result->body), $os) > 0) {
                    log::write('make-try', $name . ' try ' . $time . ' times. data: '.$result);
                    throw new \Exception("error");
                } else {
                    break;
                }
            } catch (\Exception $e) {
                log::write('make-try', $name . ' try ' . $time . ' times');
                sleep(3);
            }
            $time --;
        }
        return $result;
    }

}
