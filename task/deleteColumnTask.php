<?php
namespace task;

use api\task;

class deleteColumnTask extends  base
{

    const NAME = "delete-column";

    public function __construct()
    {
        $this->setName(self::NAME);
    }
}