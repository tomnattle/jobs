<?php
$_config = [
    'local' => [
        'domain' => "newsletter.edm-dma.com",
        'host' => "momentum-dev.webpowerchina.cn",
        'port' => 25,
        'username' => "",
        'password' => ""
    ],
    'dev' => [
        'domain' => "newsletter.edm-dma.com",
        'host' => "10.215.29.5",
        'port' => 25,
        'username' => "",
        'password' => ""
    ],
    'beta' => [
        'domain' => "newsletter.edm-dma.com",
        'host' => "10.215.29.5",
        'port' => 25,
        'username' => "",
        'password' => ""
    ],
    'prod' => [
        'domain' => "newsletter.edm-dma.com",
        'host' => "10.215.29.5",
        'port' => 25,
        'username' => "",
        'password' => ""
    ]
];

return $_config[ENV];