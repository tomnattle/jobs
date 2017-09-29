<?php
namespace command;

use util\id;

class command
{

    // id
    public $_id;

    //名称
    public $_name;

    // 日期
    public $_data;

    // 时间
    public $_time;

    // 码
    public $_code;

    //是否成功
    public $_success;

    //附带信息
    public $_message;

    // 参数为空
    const CODE_EMPTY_PARAM = 0;

    // 未知码
    const CODE_UNKNOW_NAME = 1;

    // 数据转换
    public static function purse($data)
    {
        $arr = json_decode($data, 1);
        foreach (self::getKeys() as $key) {
            if (! isset($arr[$key])) {
                throw new \Exception('key:' . $key . ' is not exsit');
            }
        }
        return self::create($arr['_name'], $arr['_data'], $arr['_code'], $arr['_success'], $arr['_message']);
    }

    // 创建一个命令对象
    public static function create($_name, $_data, $_code, $_success, $_message)
    {
        $cmd = new self();
        $cmd->_id = id::gen("command\command");
        $cmd->_name = $_name;
        $cmd->_data = $_data;
        $cmd->_time = time();
        $cmd->_code = $_code;
        $cmd->_success = $_success;
        $cmd->_message = $_message;
        return $cmd;
    }

    // 获取合法的key
    public static function getKeys()
    {
        return [
            '_success',
            '_time',
            '_code',
            '_message',
            '_id',
            '_name',
            '_data'
        ];
    }

    // 创建一个错误的命令
    public function err($code, $message)
    {
        $this->_success = false;
        $this->_code = $code;
        $this->_message = $message;
        $this->_time = time();
    }

    // 序列化
    public function toString()
    {
        $result = [
            '_success' => $this->_success,
            '_time' => $this->_time,
            '_code' => $this->_code,
            '_message' => $this->_message,
            '_id' => $this->_id,
            '_name' => $this->_name,
            '_data' => $this->_data
        ];
        return json_encode($result);
    }
}

