<?php
spl_autoload_register(array(
    'autoload',
    '__autoLoad'
));

class autoload
{

    public static function __autoLoad($className)
    {
        include_once str_replace(array(
            '\\',
            '_'
        ), '/', $className) . '.php';
    }
}
