<?php
namespace process;

class smsFactory implements IprocessFatory
{

    public static function create()
    {
        return (new sms());
    }
}

