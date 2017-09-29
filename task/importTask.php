<?php
namespace task;
use api\task as apiTask;

class importTask extends base
{

    const NAME = "contact-import";

    public function __construct()
    {
        $this->setName(self::NAME);
    }

}


