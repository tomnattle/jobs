<?php
namespace process;

use slaver\type;
use util\log;
use util\configManager;
use Httpful\Mime;
use util\file;
use models\corperate;
use task\smsSendTask;

class smsSend extends base
{

    public function getName()
    {
        return type::SMS_SEND;
    }

    public function process()
    {
        log::write ( 'p-' . $this->getName (), 'enter process' );
        //$this->getTaskManager()->fix();
        //log::write('p-' . $this->getName (), 'fixed');
        
        while (true) {
            // log::write('memery-user-sms-send', file::getMemory());
            try{
                $this->getTaskManager()->preCheck();
                $task = $this->getTaskManager()->loadReady();
                //log::write('load-sms',(!$task?'no':'yes').'found');
                if($task){
                    $result = json_decode($task->result, true);
                    if(isset($result['contact_total_count']) && ($result['contact_total_count'] > 0)){
                        corperate::notification($task->type == smsSendTask::NAME ?corperate::SMS_BEFORE_SEND:corperate::MMS_BEFORE_SEND, $task->_create_operator_uid, $task->name, 0, '');  
                        $this->_sycn($task);
                    }else{
                        if(isset($result['contact_total_count'])){
                            log::write('un-sync','invalid task [' . $task->uuid . '], reciever empty');
                            $this->getTaskManager()->finished($task->uuid);
                        }else{
                            log::write('abort','task [' . $task->uuid . '], contact_total_count not set');
                            $this->taskManager->abort($task->uuid, ['message' => 'sync failure, contact_total_count not set ' ]);  
                        }
                    }
                    $result = null;
                }
                $task = null;

            }catch(\Exception $e){
                log::write('sms/mms', 'erro happened:' . $e->getMessage());
            }
            
            sleep(1);
            
        }
    }

    public function _sycn($task){
        try{
            $task->config = json_decode($task->config);
            
            $data = [
                'activityId' => $task->id,
                'contentId' => $task->config->template_vid,
                'contentType' => $task->config->template_type == 'sms' ? 1 : 2,
                'creatorId' => $task->_create_operator_uid,
                'groupIds' => implode(',', json_decode( json_encode( $task->config->list), true)),
                'isOversea' => 0,
                'signature' => $task->config->signName,
                'status' => 1,
                'userId' => substr($task->projectid, 1),
                'vendorId' => $task->config->signId
            ];

            $uri = self::loadSmsUri('startTask');

            $response = \Httpful\Request::post($uri)
                ->sendsType(Mime::FORM)
                ->body($data)
                ->send();
                
            if( !$response || $response->code != 200 || $response->body->code != 0){
                throw new \Exception("erro happend, code :" . $response->code . ', reason:' . json_encode($response->body), 1);
            }

            $this->getTaskManager()->updateProcessing($task);
            log::write('sync', 'task  [' . $task->id . ']  sycnc success');
            $data =null;
            $response = null;
            $task = null;
            $uri = null;
        }catch(\Exception $e){ 
            log::write('abort','task [' . $task->uuid . '], ' . $e->getMessage());
            $this->taskManager->abort($task->uuid, ['message' => 'sync failure' . $e->getMessage()]);  
        }
    }


    public static function loadSmsUri($name)
    {
        $config = configManager::loadConfig('params');
        return $config['urls'][$name];
    }
}
