<?php
namespace task;

use api\task as apiTask;
use util\log;

class exportTask extends base
{

    const NAME = "contact-export";

    public function __construct()
    {
        $this->setName(self::NAME);
    }
}


