<?php
namespace process;

class exportFactory implements IprocessFatory
{

    public static function create()
    {
        return (new export());
    }
}

