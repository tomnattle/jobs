<?php
$_config = [
    'local' => [
        'ip' => '127.0.0.1',
        'port' => 9901,
        'process-count' => 10,
        'sub-process-count' => 30,
        'keepalive' => 2,
        'set' => [
            'worker_num' => 4,
            'daemonize' => false,
            'backlog' => 128,
            'task_worker_num' => 10
        ],
        'master' => [
            'ip' => '127.0.0.1',
            'port' => 9600
        ]
    ],
    'dev' => [
        'ip' => '127.0.0.1',
        'port' => 9901,
        'process-count' => 30,
        'sub-process-count' => 30,
        'keepalive' => 2,
        'set' => [
            'worker_num' => 4,
            'daemonize' => true,
            'backlog' => 128,
            'task_worker_num' => 30
        ],
        'master' => [
            'ip' => '127.0.0.1',
            'port' => 9600
        ]
    ],
    'beta' => [
        'ip' => '*.*.227.44',
        'port' => 9901,
        'process-count' => 30,
        'sub-process-count' => 30,
        'keepalive' => 2,
        'set' => [
            'worker_num' => 4,
            'daemonize' => true,
            'backlog' => 128,
            'task_worker_num' => 30
        ],
        'master' => [
            'ip' => '*.*.21.100',
            'port' => 9600
        ]
    ],
    'prod' => [
        'ip' => '10.9.72.43',
        'port' => 9901,
        'process-count' => 10,
        'sub-process-count' => 30,
        'keepalive' => 2,
        'set' => [
            'worker_num' => 4,
            'daemonize' => true,
            'backlog' => 128,
            'task_worker_num' => 10
        ],
        'master' => [
            'ip' => '10.9.70.205',
            'port' => 9600
        ]
    ]
];

return $_config[ENV];