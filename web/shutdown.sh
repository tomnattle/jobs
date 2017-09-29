#!/bin/bash
#cat runtime/$1/node/master.pid |xargs kill -15


ps -ef | grep server-master | awk '{print $2}' | xargs kill -9
ps -ef | grep php: | awk '{print $2}' | xargs kill -9
ps -ef | grep slaver-import.php | awk '{print $2}' | xargs kill -9
ps -ef | grep slaver-export.php | awk '{print $2}' | xargs kill -9
ps -ef | grep slaver-split.php | awk '{print $2}' | xargs kill -9
ps -ef | grep slaver-send.php | awk '{print $2}' | xargs kill -9
ps -ef | grep slaver-export-report.php | awk '{print $2}' | xargs kill -9


ps -ef | grep  server-standby-b.php|awk '{print $2}'|xargs kill -9
ps -ef | grep startup.php | awk '{print $2}' | xargs kill -9