<?php
$_config = [
    'local' => [
        'ip' => '127.0.0.1',
        'port' => '9800',
        'keepalive' => 2,
        'tick' => 2000,
        'set' => [
            'worker_num' => 4,
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
        'port' => '9800',
        'keepalive' => 2,
        'tick' => 2000,
        'set' => [
            'worker_num' => 4,
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
        'ip' => '127.0.0.1',
        'port' => '9800',
        'keepalive' => 2,
        'tick' => 2000,
        'set' => [
            'worker_num' => 4,
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
        'ip' => '127.0.0.1',
        'port' => '9800',
        'keepalive' => 2,
        'tick' => 2000,
        'set' => [
            'worker_num' => 4,
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

// 备用机器
$_config = array_merge_recursive($_config, [
    'local' => [
        'role' => 'standbyer',
        'master' => [
            'ip' => '127.0.0.1',
            'port' => '9600'
        ]
    ],
    'dev' => [
        'role' => 'standbyer',
        'master' => [
            'ip' => '127.0.0.1',
            'port' => '9600'
        ]
    ],
    ,
    'beta' => [
        'role' => 'standbyer',
        'master' => [
            'ip' => '10.10.21.100',
            'port' => '9600'
        ]
    ],
    ,
    'prod' => [
        'role' => 'standbyer',
        'master' => [
            'ip' => '10.10.21.100',
            'port' => '9600'
        ]
    ]
]);

return $_config[ENV];