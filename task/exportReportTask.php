<?php
namespace task;
use api\task as apiTask;

class exportReportTask extends base
{

    const NAME = "report-export";

    public function __construct()
    {
        $this->setName(self::NAME);
    }

    
}


