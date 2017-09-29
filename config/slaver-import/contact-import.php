<?php

$_config = [
    'local' => [
        'file_path' => function ($projectId, $taskId) {
            return "data/" . $projectId . "/" . $taskId . ".csv";
        },
        'json_path' => function ($projectId, $taskId) {
            return "data/" . $projectId . "/" . $taskId . "/";
        },
        'upload_uri' => function () {
            return "http://upload2.*.lc";
        },
        'downloader_uri' => function ($accessToken, $projectId, $fileName) {
            return $fileName; //return 'http://upload2.dev.*.com/?access-token=server-side&projectId=' . $projectId . '&filename=' . $fileName;
        },
        'import_api' => function ($projectId) {
            return 'http://contact2.*.dev/rest/v2/' . $projectId . '/users';
        },
        'package_limit' => 500
    ],
    'dev' => [
        'file_path' => function ($projectId, $taskId) {
            return "data/" . $projectId . "/" . $taskId . ".csv";
        },
        'json_path' => function ($projectId, $taskId) {
            return "data/" . $projectId . "/" . $taskId . "/";
        },
        'upload_uri' => function () {
            return "http://upload2.*.dev";
        },
        'downloader_uri' => function ($accessToken, $projectId, $fileName) {
            return $fileName;
        },
        'import_api' => function ($projectId) {
            return 'http://contact2.*.dev/rest/v2/' . $projectId . '/users';
        },
        'package_limit' => 500
    ],
    'beta' => [
        'file_path' => function ($projectId, $taskId) {
            return "data/" . $projectId . "/" . $taskId . ".csv";
        },
        'json_path' => function ($projectId, $taskId) {
            return "data/" . $projectId . "/" . $taskId . "/";
        },
        'upload_uri' => function () {
            return "http://*.*.62.202:8776";
        },
        'downloader_uri' => function ($accessToken, $projectId, $fileName) {
            return $fileName;
        },
        'import_api' => function ($projectId) {
            return 'http://*.*.49.130:9089/rest/v2/' . $projectId . '/users';
        },
        'package_limit' => 500
    ],
    'prod' => [
        'file_path' => function ($projectId, $taskId) {
            return "data/" . $projectId . "/" . $taskId . ".csv";
        },
        'json_path' => function ($projectId, $taskId) {
            return "data/" . $projectId . "/" . $taskId . "/";
        },
        'upload_uri' => function () {
            return "http://*.*.62.202:8776";
        },
        'downloader_uri' => function ($accessToken, $projectId, $fileName) {
            return $fileName;
        },
        'import_api' => function ($projectId) {
            return 'http://*.*.49.130:9089/rest/v2/' . $projectId . '/users';
        },
        'package_limit' => 500
    ]
];

return $_config[ENV];
