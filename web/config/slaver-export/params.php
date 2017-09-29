<?php

$_config = [
    'local' => [
        'csv_row_count' => '5',
        'token' => '27a97928edbd4763be3a193dc326a12e',
    ],
    'dev' => [
        'csv_row_count' => '500000',
        'token' => 'server-side',
    ],
    'beta' => [
        'csv_row_count' => '500000',
        'token' => 'server-side',
    ],
    'prod' => [
        'csv_row_count' => '500000',
        'token' => 'server-side',
    ]
];

return $_config[ENV];
