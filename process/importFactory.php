<?php
namespace process;

class importFactory implements IprocessFatory
{

    public static function create()
    {
        return (new import());
    }
}

