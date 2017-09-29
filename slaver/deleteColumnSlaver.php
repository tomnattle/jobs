<?php
namespace slaver;

use api\slaver;
use slaver\type;

class deleteColumnSlaver extends base
{

    public static function getName()
    {
        return type::DELETE_COLUMN;
    }

    public function __construct()
    {
        $this->setName(self::getName());
    }
}

