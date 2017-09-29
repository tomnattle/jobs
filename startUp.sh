#!/bin/bash
cd /data/wesite/wpjob/
php server-master.php
sleep 1

php slaver-import.php
sleep 1
php slaver-export.php 
sleep 1
php slaver-split.php 
sleep 1
php slaver-send.php
sleep 1
php slaver-export-report.php