<?php
namespace executer;

class importFactory
{

    public static function create()
    {
        return new import();
    }
}

