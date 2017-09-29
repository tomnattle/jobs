<?php
namespace process;

class deleteColumnFactory implements IprocessFatory
{

    public static function create()
    {
        return (new deleteColumn());
    }
}

