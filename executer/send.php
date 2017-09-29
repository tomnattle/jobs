<?php
namespace executer;

use slaver\type;
use util\log;
use conn\mongo;
use util\configManager;
use models\TaskLogManager;
use api\batch;
use models\corperate;

class send extends base
{

    private $processRate = [
        'batch_start' => 1,
        'start' => 30,
        'finish' => 100
    ];

    const ERRO = 'erro';
    private $container = [];


    public function __construct()
    {
        parent::$name = type::MAIL_SEND;
    }

    // 运行前
    public function beforeRun()
    {
        $this->container['p_id'] = $this->config->_data['projectId'];
        $this->container['task_id'] = $this->config->_data['id'];
        $this->container['collection_name'] = $this->container['p_id'] . '_' . $this->container['task_id'];
        $this->container['collection_name_event'] = $this->container['p_id'] . '_' . $this->container['task_id'] . '_events' ;
        $this->container['collection_name_template'] = $this->container['p_id'] . '_send_mail_templates';
        $this->container['batchId'] = $this->config->_data['config']['_index'];

        // var_dump($this->container['collection_name'], $this->container['batchId']);

        $mongoConfig = configManager::loadSysConfig('mongo');
        $this->container['mail_task'] = $mongoConfig[ENV]['database'];
        $this->container['mongo'] = mongo::getConn();

        $mailerConfig = configManager::loadConfig('mailer');
        $this->container['domain'] = $mailerConfig['domain'];
        $this->container['host'] = $mailerConfig['host'];
        $this->container['port'] = $mailerConfig['port'];
        $this->container['mailer'] = $this->getMailer($this->container['host'], $this->container['port']);
        $this->container['total_count'] = 0;
        $this->container['failure_count'] = 0;
        $this->container['erro_count'] = 0;

        $this->container['start_time'] = microtime(true);
        $this->container['end_time'] = 0;

        //更新 批次
        //$this->updateBatchProcessRate($this->processRate['batch_start']);
        //更新任务
        if($this->container['batchId'] == 1){
            $this->taskLogModel->addLog(TaskLogManager::ACT_START, []);
            corperate::notification(corperate::BEFORE_SEND, $this->task->_create_operator_uid, $this->task->name, 0, '');
            $this->updateProcessRate($this->processRate['start']);
        }

        $this->taskLogModel->addMonitorLog(TaskLogManager::MONITOR_ACT_BATCH_START, [
                'batch_index' => $this->container['batchId']
            ]);
    }

    // 运行
    public function run()
    {
        $container = $this->container;

        log::write('start-deal', 'task [' . $this->container['task_id'] . '][' . $this->config->_data['config']['_index'] . ']');

        $this->container['template'] = $this->getTemplate($container);
        $this->container['total_count'] = $this->fetchCount($container);
        if ($this->container['total_count'] > 0) {
            $processCount = $this->getProcessCount($this->container['total_count']);

            log::write('process', 'total_count ' . $this->container['total_count'] . ',process count ' . $processCount);

            for ($i = 0; $i < $processCount; $i ++) {
                $process = new \swoole_process(function (\swoole_process $worker) use ($container) {
                    while ($mail = $this->loadMail($container)) {
                        $mailer = $container['mailer'];
                        $this->configMailer($mailer, $mail, $container);
                        try {
                            $state = $mailer->Send();
                            //if ($state)
                            //   $this->container['failure_count'] ++;
                            //log::write('send', $mail['to_address'] . ' ' . ($state ? 'success: ' : 'failure: ' . $mailer->ErrorInfo));
                        } catch (\exception $e) {
                            $state = self::ERRO;
                            //$this->container['failure_count'] ++;
                            log::write('send', $mail['to_address'] . ' failure. ' . $e->getMessage());
                        }
                        $this->updateMail($state, $mail['_id'], $mail['id'], $container);
                    }
                }, true);
                $pid = $process->start();
                $workers[$pid] = $process;
            }

            $Pcount = 0;
            while ($ret = \swoole_process::wait(true)) {
                $Pcount ++;
                if ($Pcount == $processCount) {
                    log::write('process-exit', 'total [' . $processCount . '] process exit');
                    break;
                }
            }
        }

        log::write('deal-done', 'task [' . $this->container['task_id'] . '][' . $this->config->_data['config']['_index'] . '],total:' . $container['total_count'] );
    }

    public function getTemplate($container)
    {
        return $container['mongo']->{$container['collection_name_template']}->findOne([
            '_id' => $container['task_id']
        ]);
    }

    public function fetchCount($container)
    {
        $db = $container['mongo'];
        $count = $db->{$container['collection_name']}->count([
            'batch_id' => $container['batchId'],
            'send_status' => null,
            'lock' => null
        ]);
        return $count;
    }

    public function fetchFailureCount($container){
        $db = $container['mongo'];
        $count = $db->{$container['collection_name']}->count([
            'batch_id' => $container['batchId'],
            'send_status' => false,
        ]);
        return $count;
    }

    public function fetchErroCount($container)
    {
        $db = $container['mongo'];
        $count = $db->{$container['collection_name']}->count([
            'batch_id' => $container['batchId'],
            'send_status' => self::ERRO,
        ]);
        return $count;
    }

    public function loadTaskReport($container)
    {
        $db = $container['mongo']; 
        $data =[
            'success_count' => $db->{$container['collection_name']}->count([
                    'send_status' => true,
                ]),
            'failure_count' => $db->{$container['collection_name']}->count([
                    'send_status' => false,
                ]),
            'erro_count' => $db->{$container['collection_name']}->count([
                    'send_status' => self::ERRO,
                ]),
        ];

        return $data;
    }

    public function updateMail($state, $_id, $user_id, $container)
    {
        $db = $container['mongo'];
        $cursor = $db->{$container['collection_name']}->update([
            "_id" => $_id
        ], [
            '$set' => [
                'send_status' => $state,
                'send_time' => new \MongoDate()
            ]
        ]);
        $db->{$container['collection_name_event']}->insert([
                'user_id' => $user_id,
                'type' => 'send',
                'time' => new \MongoDate()
        ]);

    }

    public function loadMail($container)
    {
        $db = $container['mongo'];
        $data = $db->{$container['collection_name']}->findAndModify([
            'batch_id' => $container['batchId'],
            'send_status' => null,
            'lock' => null
        ], [
            '$set' => [
                'lock' => 1
            ]
        ]);
        return $data;
    }

    public function getMailer($ip, $port)
    {
        $mailer = new \PHPMailer();
        $mailer->isSMTP();
        $mailer->Host = $ip;
        $mailer->Port = $port;
        $mailer->SMTPAuth = false;
        $mailer->Username = '';
        $mailer->CharSet = "utf-8";
        return $mailer;
    }

    public function configMailer($mailer, $mail, $container)
    {
        $mailer->clearAddresses();
        $mailer->clearReplyTos();
        $mailer->clearAllRecipients();
        $mailer->clearHeaderLine();

        $headerLine = $mailer->headerLine('X-WPMESSAGEID', $container['p_id'] . '-' . $container['task_id'] . '-' .$mail['id'] . '-' . ENV . '2');
        $mailer->addHeaderLine($headerLine);
        $headerLine = $mailer->headerLine('Return-Path', 'return-to@' . $container['domain']); // $mailer->Sender = 'return-to@' . $domain;
        $mailer->addHeaderLine($headerLine);

        $mailer->setFrom($mail['from_address'], $mail['from_name']);
        $mailer->addAddress($mail['to_address'], $mail['to_name']);
        $mailer->addReplyTo($mail['from_address'], $mail['from_name']);
        $mailer->isHTML(true);
        $mailer->Subject = "=?UTF-8?B?" . base64_encode($mail['mail_subject']) . "?=";
        $mailer->Body = $this->getMailContent($mail);
        // $mailer->AltBody = '您的邮箱不支持HTML，请访问' . $mail->weblink;

        return $mailer;
    }

    public function getMailContent($mail)
    {
        $content = $this->container['template']['content'];

        // 短链
        foreach ($this->container['template']['short_links'] as $shortLink) {
            if (isset($mail['short_links'][$shortLink])) {
                $content = str_replace('{link.' . $shortLink . '}', $mail['short_links'][$shortLink], $content);
            }
        }

        // 自定义字段
        foreach ($this->container['template']['custom_field_names'] as $fieldName) {
            if (isset($mail['fields'][$fieldName])) {
                $content = str_replace('{$' . $fieldName . '}', $mail['fields'][$fieldName], $content);
            }
        }

        // 退订
        $content = str_replace('{$unsubscribe}', $mail['unsubscribe_link'], $content);

        // weblink
        $content = str_replace('{$weblink}', $mail['weblink'], $content);

        // 打开小图片，weblink页面不需要。
        $content .= '<img style="height:0px;weight:0px;" src="' . $mail['open_link'] . '" />';

        return $content;
    }

    public function getProcessCount($count)
    {
        return min(10, ceil($count / 100));
    }

    // 运行后
    public function afterRun()
    {
        $this->container['end_time'] = microtime(true);
       
        $this->container['failure_count'] = $this->fetchFailureCount($this->container);
        $this->container['erro_count'] = $this->fetchErroCount($this->container);

        $this->result = [
            'total_count' => $this->container['total_count'],
            'failure_count' => $this->container['failure_count'],
            'erro_count' => $this->container['erro_count'],
            'span' => $this->container['end_time'] - $this->container['start_time']
        ];
    }

    // 更新进度
    public function updateBatchProcessRate($num)
    {
        log::write('process-rate', 'task[' . $this->container['task_id'] . '][' . $this->config->_data['config']['_index'] . '] rate[' . $num . ']');
        $this->getTaskManager()->updateBatchProcessRate($this->batch_uuid, $num);
    }

    public function abort(){
        parent::abort();

        $total_count = $this->fetchCount($container);
        $failure_count = $this->fetchFailureCount($this->container);
        $erro_count = $this->fetchErroCount($this->container);
        $success_count = $total_count - $failure_count - $erro_count;
        
        $this->taskLogModel->addMonitorLog(TaskLogManager::MONITOR_ACT_BATCH_ABORT, [
                'abort_reason' => json_encode($this->abort),
                'task_uuid' => $this->task_uuid,
                'batch_index' => $this->config->_data['config']['_index'],
                '_status' => 'abort',
                'total_count' => $total_count,
                'success_count' => $success_count,
                'failure_count' => $failure_count,
                'erro_count' => $erro_count,
                'batch_uuid' => $this->batch_uuid,
                'status' => 'err',
                'append-message' => '提示：可进行  <span url="/admin/batch/{$batch_uuid}/retry" class="logActions" >重试</span> 操作, 或进行 <span url="/admin/batch/{$batch_uuid}/ignore" class="logActions" >忽略</span>'
            ]); 
    }

    // 保存结果
    public function saveResult()
    {
        $this->updateBatchProcessRate($this->processRate['finish']);
        $data = json_encode($this->result);
        log::write('save', $data);
        $this->taskManager->saveResult($this->batch_uuid, $data);
        $this->taskManager->finishedBatch($this->batch_uuid);
        $this->getSlaverManager()->finishJob($this->job_uuid, 'finished' , '');
        // 检查是否完成
        //$state = $this->taskManager->checkTaskFinished($this->config->_data['uuid']);
        
        //单个批次完成
        $this->taskLogModel->addMonitorLog(TaskLogManager::MONITOR_ACT_BATCH_FINISHED, [
            'batch_index' => $this->config->_data['config']['_index'],
            '_status' => $this->container['erro_count'] > 0 ? 'abort':'finshed',
            'total_count' => $this->container['total_count'],
            'success_count' => $this->container['total_count'] - $this->container['failure_count'] - $this->container['erro_count'],
            'failure_count' => $this->container['failure_count'],
            'erro_count' => $this->container['erro_count'],
            'batch_uuid' => $this->batch_uuid
        ]); 

        //批次报告
        $report = $this->taskManager->loadBatchInfoReport($this->task_uuid);
        $this->updateProcessRate($this->processRate['start'] + 70 * (($report['finished_batch_count'] + $report['cancel_batch_count'] + $report['abort_batch_count'])/$report['total_batch_count']));

        // 总批次总结束
        if(($report['finished_batch_count'] + $report['cancel_batch_count'] + $report['abort_batch_count']) == $report['total_batch_count'])
        {
            $append_message = '';
            $status = 'ok';
            if ($report['abort_batch_count'] > 0){
                $status = 'err';
                $append_message = '提示：可以尝试进行  <span url="/admin/batchs/{$task_id}/retry" class="logActions" >批量重试</a> 操作， 或进行 <span url="/admin/batchs/{$task_id}/ignore" class="logActions" >批量忽略</span>';
            }
            
            $this->taskLogModel->addMonitorLog(TaskLogManager::MONITOR_ACT_ALL_BATCH_FINISHED, [
                'task_id' => $this->task_id,
                'batch_index' => $this->config->_data['config']['_index'],
                'total_batch_count' => $report['total_batch_count'],
                'pending_batch_count' => $report['pending_batch_count'],
                'ready_batch_count' => $report['ready_batch_count'],
                'processing_batch_count' => $report['processing_batch_count'],
                'pause_batch_count' => $report['pause_batch_count'],
                'abort_batch_count' => $report['abort_batch_count'],
                'finished_batch_count' => $report['finished_batch_count'],
                'cancel_batch_count' => $report['cancel_batch_count'],
                'status' => $status,
                'append-message' => $append_message
            ]);
        }
        
        // 任务结束
        if($report['total_batch_count'] == ($report['finished_batch_count'] + $report['cancel_batch_count']))
        {   
            $taskReport = $this->loadTaskReport($this->container);

            $this->taskLogModel->addLog(TaskLogManager::ACT_FINISHED, []);
            $this->taskManager->finished($this->task_uuid);
            $this->updateProcessRate($this->processRate['finish']);
            $this->taskLogModel->addMonitorLog(TaskLogManager::MONITOR_ACT_FINISHED, [
                    'task_uuid' => $this->task_uuid,
                    'finished_batch_count' => $report['finished_batch_count'],
                    'success_count' => $taskReport['success_count'],
                    'failure_count' => $taskReport['failure_count'],
                    'erro_count' => $taskReport['erro_count']
                ]);
            corperate::notification(corperate::AFTER_SEND, $this->task->_create_operator_uid, $this->task->name, $taskReport['success_count'], '');
        }

        //触发 错误
        if($this->container['erro_count'] > 0)
            throw new Exception("erro, unsend mail count: " . $this->container['erro_count']);
    }
}
