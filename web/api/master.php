<?php
namespace api;

use ActiveRecord\Model;

// master模型
class master extends Model
{

    static $table_name = 'master';

    public static function add($uuid, $ip, $port, $role, $_status, $_created, $_updated, $p_uuid = '')
    {   
        $master = new master();
        $master->uuid = $uuid;
        $master->ip = $ip;
        $master->p_uuid = $p_uuid;
        $master->port = $port;
        $master->role = $role;
        $master->_status = $_status;
        $master->_created = $_created;
        $master->_updated = $_updated;
        $master->save();
    }

    
}

