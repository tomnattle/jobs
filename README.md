# jobs
基于swoole的 分布式任务调度 <br /> 
分布式任务调度swoole<br /> 
本项目是一个邮件分发平台的服务器中间件，采用swoole框架完成进程管理，任务异步执行，服务器通信。在web项目中，窄
一些客户的操作无法在短时间完成，或者用户创建一个定时的任务，如果多个用户创建了较多的任务，需要拓展多台机器处理
任务。比如联系人csv导入数据库，到处数据，生成报表，推送邮件等操作。本项目分为2部分，master和slaver master的
角色是负责监控任务队列，发现需要处理的任务，发给对应的salver slaver是任务处理机器，复制处理不同种类的任务mastei
单点部署，可以启动master-stanby，做备份，如果master停止，则master-standby过度为新的master,解决单点，并通知
slaver新的master信息slaver可部署在多台服务器上，单个服务器上可部署多个slaver,监听不同的端口 matser slaver
master-standby之间的保持通信，所以的节点信息及其变化都会同步在runtime的node目录。<br /> 
案例matser监听a机器9090端口 master-standby监听b机器的9091端口<br /> 
slaver-import部署在c机器监听9091端口可配置处理的进程为400类似400个通道slaver-import部署在e机器监听9092端<br /> 
口可配置处理的进程为400类似400个通道slaver-send部署在c机器监听9092端口可配置处理的进程为400类似400个通<br /> 
道slaver-send部署在e机器监听9092端口可配置处理的进程为400类似400个通道<br /> 
400个通道表示有400个任务可以同时进行<br /> 
这样就构成了一个分布的任务处理系统，当然了比较简陋，勿喷<br /> 
