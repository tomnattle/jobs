<?php

namespace models;

use models\helper\Helper;
use util\configManager;
use util\log;

/**
 * 任务日志管理器
 */
class contacts {
    // 解锁
    public static function Unlock($projectId, $listId, $taskId) {
        try {

            $config = self::loadConfig();
            $uri = $config['contact'] . "$projectId/unlock";
            //log::write('contact-unlock', 'data , param:' . json_encode($uri));

            $content = [];
            $content['entity_type'] = "list";
            $content['entity_ids'][] = $listId;
            $content['operation'] = "import";
            $content['operator_key'] = 'task:' . $taskId;

            //log::write('contact-unlock', 'data , param:' . json_encode($content));
            $response = \Httpful\Request::post($uri)->sendsType(\Httpful\Mime::JSON)
                    ->method(\Httpful\Http::PUT)
                    ->expectsJson()
                    ->body(json_encode($content))
                    ->send();
            //log::write('contact-unlock', 'data , param:' . json_encode($response->body));
            if (!$response->body->success) {
                throw new \Exception('error,server response is ' . $response->body->data);
            }
            log::write('contact', 'success , param:' . json_encode($content));
        } catch (\Exception $ex) {
            throw new \Exception('error,server response is ' . $ex->getMessage());
        }
    }
    // 加载配置
    public static function loadConfig() {
        $config = configManager::loadSysConfig('rest');
        return $config[ENV];
    }

}
