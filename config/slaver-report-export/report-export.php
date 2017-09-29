<?php
$_config = [
    'local' => [
        'urls' => [
            'raw-data-export' => function ($params) {
                //email 导出活动原始数据
                $url = "http://task2.*.dev/rest/v2/" . $params['projectId'] . "/raw-data/" . $params['taskId'];
                return $url .= "?actions=" . implode(',', $params['actions']) . "&fields=" . implode(',', $params['fields']) . "&date_start=" . $params['date_start'] . "&date_end=" . $params['date_end'] . "&page=" . $params['page'] . "&page_size=" . $params['page_size'] ;   
            },
            'domain-export' => function ($params) {
                //email 导出域名列表
                if(!$params['taskId']){
                    $url = "http://task2.*.dev/rest/v2/domain-report/count-sum";
                    return $url .= "?top=99999&projectId=" . $params['projectId'] . "&date_start=" . $params['time-span'][0] . "&date_end=" .$params['time-span'][1] . "&page=" . $params['page'] . "&page_size=" . $params['page_size'];
                }else{
                    return "http://task2.*.dev/rest/v2/domain-report/" . $params['taskId'] . "?top=99999&page=" . $params['page'] . "&page_size=" . $params['page_size'];
                }
            },
            'task-report-export' => function ($params) {
                //email 活动发送报告
                $url = "http://task2.*.dev/rest/v2/tasks";
                return $url .= "?show_task_result=1&type=mail-send&send_state=finished&projectId=" . $params['projectId'] . "&page=" . $params['page'] . "&page_size=" . $params['page_size'];
            },
            'sms-reply-export' => function ($params) {
                //sms 短信上下行
                $params['date_start'] = strtotime($params['date_start']);
                $params['date_end'] = strtotime($params['date_end']);
                $url = "http://sms-task.dev.*.com/replyDown";
                return $url .= "?userId=" . substr($params['projectId'], 1) . "&vendorId=" . $params['signId'] . "&beginTime=" . $params['date_start'] . "&endTime= " . $params['date_end'] . "&page=" . $params['page'] . "&page_size=" . $params['page_size'];
            },
            'sms-click-export' => function ($params) {
                //sms 点击报告
                $params['date_start'] = strtotime($params['date_start']);
                $params['date_end'] = strtotime($params['date_end']);
                $url = "http://sms-task.dev.*.com/sendClickReportDown";
                return $url .=  "?activityId=" . $params['taskId'] . "&beginTime=" . $params['date_start'] . "&endTime=" . $params['date_end'] . "&page=" . $params['page'] . "&page_size=" . $params['page_size'];
            },
            'sms-report-export' => function ($params) {
                //sms 报告
                $params['date_start'] = strtotime($params['date_start']);
                $params['date_end'] = strtotime($params['date_end']);
                $url = "http://sms-task.dev.*.com/reportDown";
                return $url . "?activityId=" . $params['taskId'] . "&status=" . $params['status'] . "&page=" . $params['page'] . "&page_size=" . $params['page_size'];
            },
            'mms-click-export' => function ($params) {
                //mms 点击报告
                $params['date_start'] = strtotime($params['date_start']);
                $params['date_end'] = strtotime($params['date_end']);
                $url = "http://sms-task.dev.*.com/sendClickReportDown";
                return $url .= "?activityId=" . $params['taskId'] . "&beginTime=" . $params['date_start'] . "&endTime=" . $params['date_end'] . "&page=" . $params['page'] . "&page_size=" . $params['page_size'];
            },
            'mms-report-export' => function ($params) {
                //mms 报告
                $params['date_start'] = strtotime($params['date_start']);
                $params['date_end'] = strtotime($params['date_end']);
                $url = "http://sms-task.dev.*.com/reportDown";
                return $url . "?activityId=" . $params['taskId'] . "&status=" . $params['status'] . "&page=" . $params['page'] . "&page_size=" . $params['page_size'];
            },
            'download' => function ($params) {
                $url = "http://task2.*.dev/rest/v2/downloads/" . $params['download_id'] . "?projectId=" . $params['projectId'];
                return $url;
            }
        ],
        'params'=>[
            'raw-data-export' =>[
                'projectId',
                'tasks',
                'actions',
                'fields',
                'time-span',
                'page',
                'page_size'
            ],
            'domain-export' => [
                'projectId',
                'page',
                'page_size'
            ],
            'task-report-export' => [
                'projectId',
                'page',
                'page_size'
            ],
            'sms-reply-export' => [
                'projectId',
                'signId',
                'page',
                'page_size'
            ],
            'sms-click-export' => [
                'taskId',
                'page',
                'page_size'
            ],
            'sms-report-export' => [
                'taskId',
                'status',
                'page',
                'page_size'
            ],
            'mms-click-export' => [
                'taskId',
                'page',
                'page_size'
            ],
            'mms-report-export' => [
                'taskId',
                'status',
                'page',
                'page_size'
            ],
        ],
        'headers-columns'=>[
            'raw-data-export' =>[
                '时间' => 'time',
                '类型' => 'type',
                'ip' => 'ip',
                '浏览器' => 'browser',
                '设备' => 'device'
            ],
            'domain-export' => [
                '服务商' => 'to_domain',
                '已发送' => 'send',
                '送达' => 'accept',
                '硬弹' => 'hard_bounce',
                '软弹' => 'soft_bounce',
                '退订' => 'unsubscribe',
            ],
            'task-report-export' => [
                '活动名称' => 'name',
                '开始时间' => '_started',
                '结束时间' => '_finished',
                '联系人组' => 'config.list',
                '已发送' => 'task_result.send',
                '送达' => 'task_result.accept',
            ],
            'sms-reply-export' => [
                '号码' => 'mobile_number',
                '内容' => 'content',
                '日期' => 'log_date'
            ],
            'sms-click-export' => [
                '号码' => 'mobile_number',
                '地址' => 'url',
                '时间' => 'click_date',
                'ip' => 'remote_addr',
                '浏览器' => 'browser',
                '系统' => 'os',
                '版本' => 'version'
            ],
            'sms-report-export' => [
                '号码' => 'mobile_number',
                '类型' => 'report_type',
                '长度' => 'sms_length',
                '时间' => 'log_date'
            ],
            'mms-click-export' => [
                '号码' => 'mobile_number',
                '地址' => 'url',
                '时间' => 'click_date',
                'ip' => 'remote_addr',
                '浏览器' => 'browser',
                '系统' => 'os',
                '版本' => 'version'
            ],
            'mms-report-export' => [
                '号码' => 'mobile_number',
                '类型' => 'report_type',
                '长度' => 'sms_length',
                '时间' => 'log_date'
            ],
        ],
        'max-line' => 400000,
        'page-size' => 500,
        'ufile' => [
            'upload-post' => 'http://ufile.dev.*.com/rest/v1/uploadimg'
        ],
        'file_path' => function ($projectId, $taskId) {
            return ROOT . "/data/" . $projectId . "/" . $taskId . "/";
        },
        
    ],
    'dev' => [
        'urls' => [
            'raw-data-export' => function ($params) {
                //email 导出活动原始数据
                $url = "http://task2.*.dev/rest/v2/" . $params['projectId'] . "/raw-data/" . $params['taskId'];
                return $url .= "?actions=" . implode(',', $params['actions']) . "&fields=" . implode(',', $params['fields']) . "&date_start=" . $params['date_start'] . "&date_end=" . $params['date_end'] . "&page=" . $params['page'] . "&page_size=" . $params['page_size'] ;   
            },
            'domain-export' => function ($params) {
                //email 导出域名列表
                if(!$params['taskId']){
                    $url = "http://task2.*.dev/rest/v2/domain-report/count-sum";
                    return $url .= "?top=99999&projectId=" . $params['projectId'] . "&date_start=" . $params['time-span'][0] . "&date_end=" .$params['time-span'][1] . "&page=" . $params['page'] . "&page_size=" . $params['page_size'];
                }else{
                    return "http://task2.*.dev/rest/v2/domain-report/" . $params['taskId'] . "?top=99999&page=" . $params['page'] . "&page_size=" . $params['page_size'];
                }
            },
            'task-report-export' => function ($params) {
                //email 活动发送报告
                $url = "http://task2.*.dev/rest/v2/tasks";
                return $url .= "?show_task_result=1&type=mail-send&send_state=finished&projectId=" . $params['projectId'] . "&page=" . $params['page'] . "&page_size=" . $params['page_size'];
            },
            'sms-reply-export' => function ($params) {
                $params['date_start'] = strtotime($params['date_start']);
                $params['date_end'] = strtotime($params['date_end']);
                //sms 短信上下行
                $url = "http://sms-task.dev.*.com/replyDown";
                return $url .= "?userId=" . substr($params['projectId'], 1) . "&vendorId=" . $params['signId'] . "&beginTime=" . $params['date_start'] . "&endTime= " . $params['date_end'] . "&page=" . $params['page'] . "&page_size=" . $params['page_size'];
            },
            'sms-click-export' => function ($params) {
                $params['date_start'] = strtotime($params['date_start']);
                $params['date_end'] = strtotime($params['date_end']);
                //sms 点击报告
                $url = "http://sms-task.dev.*.com/sendClickReportDown";
                return $url .=  "?activityId=" . $params['taskId'] . "&beginTime=" . $params['date_start'] . "&endTime=" . $params['date_end'] . "&page=" . $params['page'] . "&page_size=" . $params['page_size'];
            },
            'sms-report-export' => function ($params) {
                //sms 报告
                $url = "http://sms-task.dev.*.com/reportDown";
                return $url . "?activityId=" . $params['taskId'] . "&status=" . $params['status'] . "&page=" . $params['page'] . "&page_size=" . $params['page_size'];
            },
            'mms-click-export' => function ($params) {
                $params['date_start'] = strtotime($params['date_start']);
                $params['date_end'] = strtotime($params['date_end']);
                //mms 点击报告
                $url = "http://sms-task.dev.*.com/sendClickReportDown";
                return $url .= "?activityId=" . $params['taskId'] . "&beginTime=" . $params['date_start'] . "&endTime=" . $params['date_end'] . "&page=" . $params['page'] . "&page_size=" . $params['page_size'];
            },
            'mms-report-export' => function ($params) {
                //mms 报告
                $url = "http://sms-task.dev.*.com/reportDown";
                return $url . "?activityId=" . $params['taskId'] . "&status=" . $params['status'] . "&page=" . $params['page'] . "&page_size=" . $params['page_size'];
            },
            'download' => function ($params) {
                $url = "http://task2.*.dev/rest/v2/downloads/" . $params['download_id'] . "?projectId=" . $params['projectId'];
                return $url;
            }
        ],
        'params'=>[
            'raw-data-export' =>[
                'projectId',
                'tasks',
                'actions',
                'fields',
                'time-span',
                'page',
                'page_size'
            ],
            'domain-export' => [
                'projectId',
                'page',
                'page_size'
            ],
            'task-report-export' => [
                'projectId',
                'page',
                'page_size'
            ],
            'sms-reply-export' => [
                'projectId',
                'signId',
                'page',
                'page_size'
            ],
            'sms-click-export' => [
                'taskId',
                'page',
                'page_size'
            ],
            'sms-report-export' => [
                'taskId',
                'status',
                'page',
                'page_size'
            ],
            'mms-click-export' => [
                'taskId',
                'page',
                'page_size'
            ],
            'mms-report-export' => [
                'taskId',
                'status',
                'page',
                'page_size'
            ],
        ],
        'headers-columns'=>[
            'raw-data-export' =>[
                '时间' => 'time',
                '类型' => 'type',
                'ip' => 'ip',
                '浏览器' => 'browser',
                '设备' => 'device'
            ],
            'domain-export' => [
                '服务商' => 'to_domain',
                '已发送' => 'send',
                '送达' => 'accept',
                '硬弹' => 'hard_bounce',
                '软弹' => 'soft_bounce',
                '退订' => 'unsubscribe',
            ],
            'task-report-export' => [
                '活动名称' => 'name',
                '开始时间' => '_started',
                '结束时间' => '_finished',
                '联系人组' => 'config.list',
                '已发送' => 'task_result.send',
                '送达' => 'task_result.accept',
            ],
            'sms-reply-export' => [
                '号码' => 'mobile_number',
                '内容' => 'content',
                '日期' => 'log_date'
            ],
            'sms-click-export' => [
                '号码' => 'mobile_number',
                '地址' => 'url',
                '时间' => 'click_date',
                'ip' => 'remote_addr',
                '浏览器' => 'browser',
                '系统' => 'os',
                '版本' => 'version'
            ],
            'sms-report-export' => [
                '号码' => 'mobile_number',
                '类型' => 'report_type',
                '长度' => 'sms_length',
                '时间' => 'log_date'
            ],
            'mms-click-export' => [
                '号码' => 'mobile_number',
                '地址' => 'url',
                '时间' => 'click_date',
                'ip' => 'remote_addr',
                '浏览器' => 'browser',
                '系统' => 'os',
                '版本' => 'version'
            ],
            'mms-report-export' => [
                '号码' => 'mobile_number',
                '类型' => 'report_type',
                '长度' => 'sms_length',
                '时间' => 'log_date'
            ],
        ],
        'max-line' => 400000,
        'page-size' => 500,
        'ufile' => [
            'upload-post' => 'http://ufile.dev.*.com/rest/v1/uploadimg'
        ],
        'file_path' => function ($projectId, $taskId) {
            return ROOT . "/data/" . $projectId . "/" . $taskId . "/";
        },
    ],
    'beta' => [
        'urls' => [
            'raw-data-export' => function ($params) {
                //email 导出活动原始数据
                $url = "http://*.*.49.130:9099/rest/v2/" . $params['projectId'] . "/raw-data/" . $params['taskId'];
                return $url .= "?actions=" . implode(',', $params['actions']) . "&fields=" . implode(',', $params['fields']) . "&date_start=" . $params['date_start'] . "&date_end=" . $params['date_end'] . "&page=" . $params['page'] . "&page_size=" . $params['page_size'] ;   
            },
            'domain-export' => function ($params) {
                //email 导出域名列表
                if(!$params['taskId']){
                    $url = "http://*.*.49.130:9099/rest/v2/domain-report/count-sum";
                    return $url .= "?top=99999&projectId=" . $params['projectId'] . "&date_start=" . $params['time-span'][0] . "&date_end=" .$params['time-span'][1] . "&page=" . $params['page'] . "&page_size=" . $params['page_size'];
                }else{
                    return "http://*.*.49.130:9099/rest/v2/domain-report/" . $params['taskId'] . "?top=99999&page=" . $params['page'] . "&page_size=" . $params['page_size'];
                }
            },
            'task-report-export' => function ($params) {
                //email 活动发送报告
                $url = "http://*.*.49.130:9099/rest/v2/tasks";
                return $url .= "?show_task_result=1&type=mail-send&send_state=finished&projectId=" . $params['projectId'] . "&page=" . $params['page'] . "&page_size=" . $params['page_size'];
            },
            'sms-reply-export' => function ($params) {
                //sms 短信上下行
                $params['date_start'] = strtotime($params['date_start']);
                $params['date_end'] = strtotime($params['date_end']);
                $url = "http://sms-task.dev.*.com/replyDown";
                return $url .= "?userId=" . substr($params['projectId'], 1) . "&vendorId=" . $params['signId'] . "&beginTime=" . $params['date_start'] . "&endTime= " . $params['date_end'] . "&page=" . $params['page'] . "&page_size=" . $params['page_size'];
            },
            'sms-click-export' => function ($params) {
                //sms 点击报告
                $params['date_start'] = strtotime($params['date_start']);
                $params['date_end'] = strtotime($params['date_end']);
                $url = "http://sms-task.dev.*.com/sendClickReportDown";
                return $url .=  "?activityId=" . $params['taskId'] . "&beginTime=" . $params['date_start'] . "&endTime=" . $params['date_end'] . "&page=" . $params['page'] . "&page_size=" . $params['page_size'];
            },
            'sms-report-export' => function ($params) {
                //sms 报告
                $url = "http://sms-task.dev.*.com/reportDown";
                return $url . "?activityId=" . $params['taskId'] . "&status=" . $params['status'] . "&page=" . $params['page'] . "&page_size=" . $params['page_size'];
            },
            'mms-click-export' => function ($params) {
                //mms 点击报告
                $params['date_start'] = strtotime($params['date_start']);
                $params['date_end'] = strtotime($params['date_end']);
                $url = "http://sms-task.dev.*.com/sendClickReportDown";
                return $url .= "?activityId=" . $params['taskId'] . "&beginTime=" . $params['date_start'] . "&endTime=" . $params['date_end'] . "&page=" . $params['page'] . "&page_size=" . $params['page_size'];
            },
            'mms-report-export' => function ($params) {
                //mms 报告
                $url = "http://sms-task.dev.*.com/reportDown";
                return $url . "?activityId=" . $params['taskId'] . "&status=" . $params['status'] . "&page=" . $params['page'] . "&page_size=" . $params['page_size'];
            },
            'download' => function ($params) {
                $url = "http://*.*.49.130:9099/rest/v2/downloads/" . $params['download_id'] . "?projectId=" . $params['projectId'];
                return $url;
            }
        ],
        'params'=>[
            'raw-data-export' =>[
                'projectId',
                'tasks',
                'actions',
                'fields',
                'time-span',
                'page',
                'page_size'
            ],
            'domain-export' => [
                'projectId',
                'page',
                'page_size'
            ],
            'task-report-export' => [
                'projectId',
                'page',
                'page_size'
            ],
            'sms-reply-export' => [
                'projectId',
                'signId',
                'page',
                'page_size'
            ],
            'sms-click-export' => [
                'taskId',
                'page',
                'page_size'
            ],
            'sms-report-export' => [
                'taskId',
                'status',
                'page',
                'page_size'
            ],
            'mms-click-export' => [
                'taskId',
                'page',
                'page_size'
            ],
            'mms-report-export' => [
                'taskId',
                'status',
                'page',
                'page_size'
            ],
        ],
        'headers-columns'=>[
            'raw-data-export' =>[
                '时间' => 'time',
                '类型' => 'type',
                'ip' => 'ip',
                '浏览器' => 'browser',
                '设备' => 'device'
            ],
            'domain-export' => [
                '服务商' => 'to_domain',
                '已发送' => 'send',
                '送达' => 'accept',
                '硬弹' => 'hard_bounce',
                '软弹' => 'soft_bounce',
                '退订' => 'unsubscribe',
            ],
            'task-report-export' => [
                '活动名称' => 'name',
                '开始时间' => '_started',
                '结束时间' => '_finished',
                '联系人组' => 'config.list',
                '已发送' => 'task_result.send',
                '送达' => 'task_result.accept',
            ],
            'sms-reply-export' => [
                '号码' => 'mobile_number',
                '内容' => 'content',
                '日期' => 'log_date'
            ],
            'sms-click-export' => [
                '号码' => 'mobile_number',
                '地址' => 'url',
                '时间' => 'click_date',
                'ip' => 'remote_addr',
                '浏览器' => 'browser',
                '系统' => 'os',
                '版本' => 'version'
            ],
            'sms-report-export' => [
                '号码' => 'mobile_number',
                '类型' => 'report_type',
                '长度' => 'sms_length',
                '时间' => 'log_date'
            ],
            'mms-click-export' => [
                '号码' => 'mobile_number',
                '地址' => 'url',
                '时间' => 'click_date',
                'ip' => 'remote_addr',
                '浏览器' => 'browser',
                '系统' => 'os',
                '版本' => 'version'
            ],
            'mms-report-export' => [
                '号码' => 'mobile_number',
                '类型' => 'report_type',
                '长度' => 'sms_length',
                '时间' => 'log_date'
            ],
        ],
        'max-line' => 400000,
        'page-size' => 500,
        'ufile' => [
            'upload-post' => 'http://*.*.49.130:9086/rest/v1/uploadimg'
        ],
        'file_path' => function ($projectId, $taskId) {
            return ROOT . "/data/" . $projectId . "/" . $taskId . "/";
        },
    ],
    'prod' => [
        'urls' => [
            'raw-data-export' => function ($params) {
                //email 导出活动原始数据
                $url = "http://*.*.49.130:9099/rest/v2/" . $params['projectId'] . "/raw-data/" . $params['taskId'];
                return $url .= "?actions=" . implode(',', $params['actions']) . "&fields=" . implode(',', $params['fields']) . "&date_start=" . $params['date_start'] . "&date_end=" . $params['date_end'] . "&page=" . $params['page'] . "&page_size=" . $params['page_size'] ;   
            },
            'domain-export' => function ($params) {
                //email 导出域名列表
                if(!$params['taskId']){
                    $url = "http://*.*.49.130:9099/rest/v2/domain-report/count-sum";
                    return $url .= "?projectId=" . $params['projectId'] . "&date_start=" . $params['time-span'][0] . "&date_end=" .$params['time-span'][1] . "&page=" . $params['page'] . "&page_size=" . $params['page_size'];
                }else{
                    return "http://*.*.49.130:9099/rest/v2/domain-report/" . $params['taskId'] . "?page=" . $params['page'] . "&page_size=" . $params['page_size'];
                }
            },
            'task-report-export' => function ($params) {
                //email 活动发送报告
                $url = "http://*.*.49.130:9099/rest/v2/tasks";
                return $url .= "?show_task_result=1&type=mail-send&send_state=finished&projectId=" . $params['projectId'] . "&page=" . $params['page'] . "&page_size=" . $params['page_size'];
            },
            'sms-reply-export' => function ($params) {
                //sms 短信上下行
                $url = "http://sms-task.dev.*.com/replyDown";
                return $url .= "?userId=" . substr($params['projectId'], 1) . "&vendorId=" . $params['signId'] . "&beginTime=" . $params['date_start'] . "&endTime= " . $params['date_end'] . "&page=" . $params['page'] . "&page_size=" . $params['page_size'];
            },
            'sms-click-export' => function ($params) {
                //sms 点击报告
                $url = "http://sms-task.dev.*.com/sendClickReportDown";
                return $url .=  "activityId=" . $params['taskId'] . "&beginTime=" . $params['date_start'] . "&endTime=" . $params['date_end'] . "&page=" . $params['page'] . "&page_size=" . $params['page_size'];
            },
            'sms-report-export' => function ($params) {
                //sms 报告
                $url = "http://sms-task.dev.*.com/reportDown";
                return $url . "?activityId=" . $params['taskId'] . "&status=" . $params['status'] . "&page=" . $params['page'] . "&page_size=" . $params['page_size'];
            },
            'mms-click-export' => function ($params) {
                //mms 点击报告
                $url = "http://sms-task.dev.*.com/sendClickReportDown";
                return $url .= "activityId=" . $params['taskId'] . "&beginTime=" . $params['date_start'] . "&endTime=" . $params['date_end'] . "&page=" . $params['page'] . "&page_size=" . $params['page_size'];
            },
            'mms-report-export' => function ($params) {
                //mms 报告
                $url = "http://sms-task.dev.*.com/reportDown";
                return $url . "?activityId=" . $params['taskId'] . "&status=" . $params['status'] . "&page=" . $params['page'] . "&page_size=" . $params['page_size'];
            },
            'download' => function ($params) {
                $url = "http://*.*.49.130:9099/rest/v2/downloads/" . $params['download_id'] . "?projectId=" . $params['projectId'];
                return $url;
            }
        ],
        'params'=>[
            'raw-data-export' =>[
                'projectId',
                'tasks',
                'actions',
                'fields',
                'time-span',
                'page',
                'page_size'
            ],
            'domain-export' => [
                'projectId',
                'page',
                'page_size'
            ],
            'task-report-export' => [
                'projectId',
                'page',
                'page_size'
            ],
            'sms-reply-export' => [
                'projectId',
                'signId',
                'page',
                'page_size'
            ],
            'sms-click-export' => [
                'taskId',
                'page',
                'page_size'
            ],
            'sms-report-export' => [
                'taskId',
                'status',
                'page',
                'page_size'
            ],
            'mms-click-export' => [
                'taskId',
                'page',
                'page_size'
            ],
            'mms-report-export' => [
                'taskId',
                'status',
                'page',
                'page_size'
            ],
        ],
        'headers-columns'=>[
            'raw-data-export' =>[
                '时间' => 'time',
                '类型' => 'type',
                'ip' => 'ip',
                '浏览器' => 'browser',
                '设备' => 'device'
            ],
            'domain-export' => [
                '服务商' => 'to_domain',
                '已发送' => 'send',
                '送达' => 'accept',
                '硬弹' => 'hard_bounce',
                '软弹' => 'soft_bounce',
                '退订' => 'unsubscribe',
            ],
            'task-report-export' => [
                '活动名称' => 'name',
                '开始时间' => '_started',
                '结束时间' => '_finished',
                '联系人组' => 'config.list',
                '已发送' => 'task_result.send',
                '送达' => 'task_result.accept',
            ],
            'sms-reply-export' => [
                '号码' => 'mobile_number',
                '内容' => 'content',
                '日期' => 'log_date'
            ],
            'sms-click-export' => [
                '号码' => 'mobile_number',
                '地址' => 'url',
                '时间' => 'click_date',
                'ip' => 'remote_addr',
                '浏览器' => 'browser',
                '系统' => 'os',
                '版本' => 'version'
            ],
            'sms-report-export' => [
                '号码' => 'mobile_number',
                '类型' => 'report_type',
                '长度' => 'sms_length',
                '时间' => 'log_date'
            ],
            'mms-click-export' => [
                '号码' => 'mobile_number',
                '地址' => 'url',
                '时间' => 'click_date',
                'ip' => 'remote_addr',
                '浏览器' => 'browser',
                '系统' => 'os',
                '版本' => 'version'
            ],
            'mms-report-export' => [
                '号码' => 'mobile_number',
                '类型' => 'report_type',
                '长度' => 'sms_length',
                '时间' => 'log_date'
            ],
        ],
        'max-line' => 400000,
        'page-size' => 500,
        'ufile' => [
            'upload-post' => 'http://*.*.49.130:9086/rest/v1/uploadimg'
        ],
        'file_path' => function ($projectId, $taskId) {
            return ROOT . "/data/" . $projectId . "/" . $taskId . "/";
        },
    ],
];

return $_config[ENV];
