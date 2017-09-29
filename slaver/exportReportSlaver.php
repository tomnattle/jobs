<?php
namespace slaver;

use api\slaver;
use slaver\type;

class exportReportSlaver extends base
{

    public static function getName()
    {
        return type::REPORT_EXPORT;
    }

    public function __construct()
    {
        $this->setName(self::getName());
    }
}

