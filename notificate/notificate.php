<?php
namespace notificate;

class notificate
{

    const SLAVER_EXIT = 0;

    const STANDBY_EXIT = 1;

    const MASTER_EXIT = 2;

    const TASK_START = 4;

    const TASK_ABORT = 5;

    const TASK_FINISH = 6;
    // 废弃
    public static function nofity($type, $data)
    {
        $message = "";
        $pattern = "#time #type #data \n";
        $message = str_replace("#time", date('Y-m-d H:i:s'), $pattern);
        $message = str_replace("#type", $type, $pattern);
        $message = str_replace("#data", date('Y-m-d H:i:s'), json_encode($data));
        file_put_contents(ROOT . "/runtime/" . _NAMESPACE . "/log/notification.log", $message, FILE_APPEND);
    }
}
