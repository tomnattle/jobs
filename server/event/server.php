<?php
namespace server\event;
// 事件
class server
{
    // 启动
    const START_UP = "start_up";
    // 关机
    const SHUT_DOWN = "shut_down";
    // 启动后
    const AFTER_START_UP = "after_start_up";
    // 伺服注册
    const SLAVER_REGISTER = "slaver_register";
    // 备用机注册
    const STANDBY_REGISTER = "standby_register";
    // 同步
    const SYNC = "sync";
    // 主机角色转变
    const STANDBYER_TO_MASTER = "standbyer_to_master";
    // 注册后
    const AFTER_REGISTER = "after_register";
    // 主机丢失
    const MASTER_LOST = "master_lost";
    // 伺服丢失
    const SLAVER_LOST = "slaver_lost";
    // 备用机丢失
    const STANDBY_LOST = "standby_lost";
    // 注册后
    const AFTER_STANDBY_REGISTER = "after_standby_register";
    // 启动后
    const WORKER_START = "worker_start";
    // 开始进程
    const SATRT_PROCESS = "start_process";
    // 保持slaver存活
    const KEEP_SLAVER_ALIVE = "keep_slaver_alive";
    // 保持standby存活
    const KEEP_STANDBY_ALIVE = "keep_standby_alive";
    // 保持master存活
    const KEEP_MASTER_ALIVE = "keep_master_alive";
}

