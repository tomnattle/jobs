<?php
namespace slaver;

use api\slaver;
use slaver\type;

class mailSplitSlaver extends base
{

    public static function getName()
    {
        return type::MAIL_SPLIT;
    }

    public function __construct()
    {
        $this->setName(self::getName());
    }
}

