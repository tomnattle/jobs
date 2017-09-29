<?php
namespace slaver;

use api\slaver;
use slaver\type;

class smsSendSlaver extends base
{

    public static function getName()
    {
        return type::SMS_SEND;
    }

    public function __construct()
    {
        $this->setName(self::getName());
    }
}

