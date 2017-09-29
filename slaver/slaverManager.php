<?php
namespace slaver;

use api\slaver;
use slaver\type;

class slaverManager
{

    public $slaver = null;
    // 获取slaver
    public function __construct($obj)
    {
        switch ($obj->getName()) {
            case type::CONTACT_IMPORT:
                $this->slaver = new importSlaver();
                break;
            case type::CONTACT_EXPORT:
                $this->slaver = new exportSlaver();
                break;
            case type::MAIL_SPLIT:
                $this->slaver = new mailSplitSlaver();
                break;
            case type::MAIL_SEND:
                $this->slaver = new mailSendSlaver();
                break;
            case type::DELETE_COLUMN:
                $this->slaver = new deleteColumnSlaver();
                break;
            case type::REPORT_EXPORT:
                $this->slaver = new exportReportSlaver();
                break;
            case type::SMS_SEND:
                $this->slaver = new smsSendSlaver();
                break;
            default:
                throw new \Exception('unknow type of obj');
        }
        // print_r($obj->getName() . "---------" . $this->slaver->getName() . "\n");
    }

    public function assignJob($jobId, $slaver, $task)
    {
        $this->slaver->assignJob($jobId, $slaver, $task);
    }

    public function finishJob($job_uuid, $_status = 'finished', $abort_reason)
    {
        $this->slaver->finishJob($job_uuid, $_status, $abort_reason);
    }

    public function loadFrees($master_uuid)
    {
        return $this->slaver->loadFrees($master_uuid);
    }
}

