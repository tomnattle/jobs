<?php

namespace models;

use util\configManager;
use util\log;
// 第三方corporater接口
class corperate {

    // 导入完毕
    const AFTER_IMPORT = 1;
    // 发送开始
    const BEFORE_SEND = 2;
    // 发送完毕
    const AFTER_SEND = 3;
    // 余量不足
    const LACK_QUANTITY = 4;
    // 日量不足
    const LACK_DAY_QUANTITY = 5;
    // 未知错误
    const UNKOWN_ERROR = 6;
    // 扣费成功
    const DEDUCT_SUCESS = 0;
    // 短信发送开始
    const SMS_BEFORE_SEND = 7;
    // 彩信发送开始
    const MMS_BEFORE_SEND = 13;

    // 扣费
    public static function deduct($user_id, $taskId, $count) {
        try {
            $config = self::loadConfig();
            $uri = $config['corperate'] . 'business/email/recharge';

            $data = [
                'user_id' => $user_id,
                'oper' => 0,
                'quantity' => $count,
                'memo' => 'date:' . date("Y-m-d H:i:s") . ' . id:' . $taskId
            ];

            $response = \Httpful\Request::post($uri)
                    ->sendsType(\Httpful\Mime::FORM)
                    ->expectsJson()
                    ->body($data)
                    ->send();

            $type = self::DEDUCT_SUCESS;
            if ($response->body->code != 200) {
                if ($response->body->code == 1020) {
                    $type = self::LACK_QUANTITY;
                } elseif ($response->body->code == 1021) {
                    $type = self::LACK_DAY_QUANTITY;
                } else {
                    throw new \Exception("reson " . $response->body->message . ", unkown code :" . $response->body->code, 1);
                }
            }
            return $type;
        } catch (\Exception $e) {
            log::write("deduct-failure", "deduct failure, " . $e->getMessage(), 1);
            return self::UNKOWN_ERROR;
        }
    }
    // 通知接口
    public static function notification($type, $user_id, $activity_name, $email_user_count, $list_name) {

        try{
            $config = self::loadConfig();
            $uri = $config['corperate'] . 'utils/remindSend';

            $content = [];
            $content['activity_name'] = $activity_name;
            $content['email_user_count'] = $email_user_count;
            $content['list_name'] = $list_name;

            $response = \Httpful\Request::post($uri)->sendsType(\Httpful\Mime::FORM)
                    ->method(\Httpful\Http::POST)
                    ->expectsJson()
                    ->body('content=' . json_encode($content) . '&type=' . $type . '&user_id=' . $user_id)
                    ->send();

            if ($response->body->code != 200) {
                throw new \Exception('error,server response is ' . $response->body->code);
            }
        }catch(\Exception $e){
            log::write('notification-erro', 'content=' . json_encode($content) . '&type=' . $type . '&user_id=' . $user_id);
        }
        log::write('notification', 'success , param:' . 'content=' . json_encode($content) . '&type=' . $type . '&user_id=' . $user_id);
    }
    // 加载配置
    public static function loadConfig() {
        $config = configManager::loadSysConfig('rest');
        return $config[ENV];
    }

}
