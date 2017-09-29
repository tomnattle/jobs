<?php
namespace slaver;

use api\slaver;
use slaver\type;

class importSlaver extends base
{

    public static function getName()
    {
        return type::CONTACT_IMPORT;
    }

    public function __construct()
    {
        $this->setName(self::getName());
    }
}
