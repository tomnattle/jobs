<?php
namespace event;

use event\IEvent;
use util\log;
use util\sys;

class eventManager implements IeventManager
{

    public static $events = [];

    // 添加事件
    public static function addEvent($event)
    {
        if (! $event instanceof IEvent)
            throw new \Exception("event should be implement Ievent");
        
        self::$events[strtolower($event->getName())][] = $event;
    }
    // 添加回调
    public static function addCallBack($name = null, $func = null)
    {
        self::$events[strtolower($name)][] = $func;
    }
    // 触发事件
    public static function triggle($name, $data = null)
    {
        //log::write('memery-user-event-manager',$name . '-' . sys::getMemory(true));
        foreach (self::$events as $key => $events) {
            if (strtolower($name) == $key) {
                foreach ($events as $event) {
                    if ($event instanceof IEvent) {
                        $event->run($data);
                    } else {
                        call_user_func($event, $data);
                    }
                }
            }
        }
    }
}

