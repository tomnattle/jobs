<?php
return [
    'buildDir' => [
        'root' => 'runtime/_namespace/',
        'node_root' => 'runtime/_namespace/node/',
        'standby' => 'runtime/_namespace/node/standby/',
        'slaver' => 'runtime/_namespace/node/slaver/',
        'master' => 'runtime/_namespace/node/master/',
        'log' => 'runtime/_namespace/log/',
        'server' => 'runtime/_namespace/message/',
        'data' => 'data/_namespace/'
    ],
    'clearDir' => [
        'standby' => 'runtime/_namespace/node/standby/',
        'slaver' => 'runtime/_namespace/node/slaver/',
        'master' => 'runtime/_namespace/node/master/',
        'synclock' => 'runtime/_namespace/node/',
        'standbylock' => 'runtime/_namespace/node/isStandby.flag',
        'masterlock' => 'runtime/_namespace/node/isMaster.flag'
    ]
]
;