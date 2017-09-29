<?php
namespace executer;

class exportFactory
{

    public static function create()
    {
        return new export();
    }
}

