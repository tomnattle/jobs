<?php
// 短彩信开始时调用的端口
$_config = [
    'local' => [
        'urls' => [
        	'startTask'=>'http://sms-task.dev.dmayun.com/startTask'
        ]
    ],
    'dev' => [
        'urls' => [
            'startTask'=>'http://sms-task.dev.dmayun.com/startTask'
        ]
    ],
    'beta' => [
        'urls' => [
            'startTask'=>'http://sms-task.beta.dmayun.com/startTask'
        ]
    ],
    'prod' => [
        'urls' => [
            'startTask'=>'http://sms-task.beta.dmayun.com/startTask'
        ]
    ]
];

return $_config[ENV];