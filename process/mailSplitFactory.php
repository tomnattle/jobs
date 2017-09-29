<?php
namespace process;

class mailSplitFactory implements IprocessFatory
{

    public static function create()
    {
        return (new mailSplit());
    }
}

