<?php
namespace process;

class mailSendFactory implements IprocessFatory
{

    public static function create()
    {
        return (new mailSend());
    }
}

