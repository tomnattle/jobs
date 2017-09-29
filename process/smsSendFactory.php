<?php
namespace process;

class smsSendFactory implements IprocessFatory
{

    public static function create()
    {
        return (new smsSend());
    }
}

