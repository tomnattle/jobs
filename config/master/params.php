<?php
// 短彩信开始时调用的端口
$_config = [
    'local' => [
        'urls' => [
        	'startTask'=>'http://sms-task.dev.*.com/startTask'
        ]
    ],
    'dev' => [
        'urls' => [
            'startTask'=>'http://sms-task.dev.*.com/startTask'
        ]
    ],
    'beta' => [
        'urls' => [
            'startTask'=>'http://sms-task.beta.*.com/startTask'
        ]
    ],
    'prod' => [
        'urls' => [
            'startTask'=>'http://sms-task.beta.*.com/startTask'
        ]
    ]
];

return $_config[ENV];