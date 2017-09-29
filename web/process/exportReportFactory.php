<?php
namespace process;

class exportReportFactory implements IprocessFatory
{

    public static function create()
    {
        return (new exportReport());
    }
}

