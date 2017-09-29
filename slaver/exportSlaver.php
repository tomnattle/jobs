<?php
namespace slaver;

use api\slaver;
use slaver\type;

class exportSlaver extends base
{

    public static function getName()
    {
        return type::CONTACT_EXPORT;
    }

    public function __construct()
    {
        $this->setName(self::getName());
    }
}

