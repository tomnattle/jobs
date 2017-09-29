<?php
namespace event;

// 事件管理器
interface IeventManager
{
    // 添加事件
    public static function addEvent($event);
    // 添加回调
    public static function addCallBack($name = null, $func = null);
    // 触发
    public static function triggle($name);
}

