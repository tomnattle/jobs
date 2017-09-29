<?php

namespace models;

use models\helper\Helper;
use util\configManager;

/**
 * 任务日志管理器
 */
class TaskLogManager
{
    // 环境配置信息
    private $configEnv;

    // 项目Id
    private $projectId;

    // 任务Id
    private $taskId;

    const ACT_PRE_START = '发送任务开始预处理 {$datetime}';

    const ACT_PRE_END = '发送任务预处理完成，等待发送 {$datetime}';

    const ACT_ABORT = '任务中止 {$datetime}';

    const ACT_START = '任务开始发送 {$datetime}';

    const ACT_PAUSE_UNBLANCE ='余额不足，发送暂停，暂停任务系统保存15天，15天内未恢复发送的任务将自动结束，请立即充值恢复发送 {$datetime}';

    const ACT_FINISHED = '发送结束 {$datetime}';

    const ACT_UNVALID = '任务暂停超过15天，自动结束 {$datetime}';

    const ACT_EMPTY_LIST_FINISHED = '无可用收件人数据，发送自动停止 {$datetime}';
    

    const MONITOR_ACT_PRE_START = '{$datetime} - 预处理开始';

    const MONITOR_ACT_PRE_ABORT = '{$datetime} - 预处理预处理出错，原因：{$abort_reason} ';

    const MONITOR_ACT_PRE_FINISHED = '{$datetime} - 预处理完成 ，活动分批次已处理完成，目标联系人总计：{$count}，分{$batch_count}批发送';

    const MONITOR_ACT_BATCH_START = '{$datetime} - 开始发送第{$batch_index}批';

    const MONITOR_ACT_BATCH_FINISHED = '{$datetime} - 完成第{$batch_index}批，状态：{$_status} 总数：{$total_count} 成功：{$success_count} 失败：{$failure_count} ';

    const MONITOR_ACT_BATCH_ABORT = '{$datetime} - 完成第{$batch_index}批，状态：{$_status} 成功：{$finished_count} 失败：{$failure_count} 错误：{$erro_count}';

    const MONITOR_ACT_ALL_BATCH_FINISHED = '{$datetime} - 所有批次发送完成，成功批次：{$finished_batch_count}，失败批次：{$abort_batch_count} ';

    const MONITOR_ACT_FINISHED = '{$datetime} - 发送结束。成功{$finished_batch_count}批次，成功{$success_count}人，失败{$failure_count}人，错误{$erro_count}人';

    const MONITOR_ACT_PAUSE_UNBLANCE ='{$datetime} - 余额不足，发送暂停，15天内未恢复发送的任务将自动结束';

    const MONITOR_ACT_EMPTY_LIST_FINISHED = '{$datetime} - 无可用收件人数据，发送自动停止';

    /**
     * 构造函数
     *
     * @param string $projectId 项目Id
     * @param number $taskId 任务Id
     */

    public function __construct($projectId, $taskId)
    {
        $this->projectId = $projectId;
        $this->taskId = $taskId;

        // 读取环境信息
        $restConfig = configManager::loadSysConfig('rest');
        $mongodbTaskConfig = configManager::loadSysConfig('mongo');
        $this->configEnv = [
            'rest' => $restConfig[ENV],
            'mongodb' => [
                'task' => $mongodbTaskConfig[ENV],
            ],
        ];
    }

    /**
     * 添加日志
     * @param string $message 日志信息
     * @param array  $data    相关数据
     *
     * @return boolean 是否成功
     *
     * example:
     *
     * $taskLogManager = new \models\TaskLogManager('test', 12345);
     * $taskLogManager->addLog('server is down.', ['reason' => 'no power']);
     *
     */
    public function addLog($message, array $data = [])
    {
        $message = str_replace('{$datetime}',date('Y-m-d H:i:s', time()), $message);
        $url = $this->configEnv['rest']['task'] . 'tasklogs?projectId=' . $this->projectId . '&task_id=' . $this->taskId;
        $json = json_encode(['message' => $message, 'data' => $data]);

        $response = \Httpful\Request::post($url)->sendsJson()->body($json)->send();
        if ($response->body->success) {
            return true;
        } else {
            return false;
        }
    }

    // 添加监控日志
    public function addMonitorLog($message, $data = [])
    {
        $data['datetime'] = date('Y-m-d H:i:s', time());

        if(!isset($data['status']))
            $data['status'] = 'ok';

        foreach ($data as $key => $value) {
            $message = str_replace('{$' . $key . '}', $value, $message);  
            if(isset($data['append-message']))
                $data['append-message'] = str_replace('{$' . $key . '}', $value, $data['append-message']); 
        }

        $url = $this->configEnv['rest']['task'] . 'tasklogs?projectId=' . $this->projectId . '&task_id=' . $this->taskId;
        $json = json_encode(['message' => $message, 'data' => $data, 'type' => 'monitor']);

        $response = \Httpful\Request::post($url)->sendsJson()->body($json)->send();
        if ($response->body->success) {
            return true;
        } else {
            return false;
        }
    }
}