<?php

namespace models\helper;

class MysqlHelper {
    // 获取时间
    public static function getMysqlNowString()
    {
        return date('Y-m-d H:i:s');
    }
}
