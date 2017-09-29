<?php
namespace api;

use ActiveRecord\Model;

// slaver模型
class slaver extends Model
{

    static $table_name = 'slaver';

    public static function add($uuid, $master_uuid, $ip, $port, $_status, $_created, $_updated, $executer, $process_count)
    {
        $slaver = new slaver();
        $slaver->uuid = $uuid;
        $slaver->master_uuid = $master_uuid;
        $slaver->ip = $ip;
        $slaver->port = $port;
        $slaver->_status = $_status;
        $slaver->_created = $_created;
        $slaver->_updated = $_updated;
        $slaver->executer = $executer;
        $slaver->process_count = $process_count;
        $slaver->save();
    }
}

