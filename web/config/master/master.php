<?php
// 参考swoole官网
$_config = [
    'local' => [
        'ip' => '127.0.0.1',
        'port' => '9600',
        'role' => 'master',
        'keepalive' => 2,
        'tick' => 2000,
        'set' => [
            'worker_num' => 1,
            'daemonize' => false,
            'backlog' => 128
        ],
        'supportCmds' => [
            'register',
            'b',
            'c'
        ]
    ],
    'dev' => [
        'ip' => '127.0.0.1',
        'port' => '9600',
        'role' => 'master',
        'keepalive' => 2,
        'tick' => 2000,
        'set' => [
            'worker_num' => 1,
            'daemonize' => true,
            'backlog' => 128
        ],
        'supportCmds' => [
            'register',
            'b',
            'c'
        ]
    ],
    'beta' => [
        'ip' => '10.10.21.100',
        'port' => '9600',
        'role' => 'master',
        'keepalive' => 2,
        'tick' => 2000,
        'set' => [
            'worker_num' => 1,
            'daemonize' => true,
            'backlog' => 128
        ],
        'supportCmds' => [
            'register',
            'b',
            'c'
        ]
    ],
    'prod' => [
        'ip' => '10.9.70.205',
        'port' => '9600',
        'role' => 'master',
        'keepalive' => 2,
        'tick' => 2000,
        'set' => [
            'worker_num' => 1,
            'daemonize' => true,
            'backlog' => 128
        ],
        'supportCmds' => [
            'register',
            'b',
            'c'
        ]
    ]
];

return $_config[ENV];