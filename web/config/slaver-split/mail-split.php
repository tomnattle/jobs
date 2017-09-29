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
            return "http://upload.wpcn.lc";
        },
        'downloader_uri' => function ($accessToken, $projectId, $fileName) {
            return 'http://upload.dev.dmayun.com/?access-token=server-side&projectId=' . $projectId . '&filename=' . $fileName;
        },
        'import_api' => function ($projectId) {
            return 'http://contact2.wpcn.dev/rest/v2/' . $projectId . '/users';
        }
    ],
    'dev' => [
        'file_path' => function ($projectId, $taskId) {
            return "data/" . $projectId . "/" . $taskId . ".csv";
        },
        'json_path' => function ($projectId, $taskId) {
            return "data/" . $projectId . "/" . $taskId . "/";
        },
        'upload_uri' => function () {
            return "http://upload2.wpcn.dev";
        },
        'downloader_uri' => function ($accessToken, $projectId, $fileName) {
            return $fileName;//'http://upload2.dev.dmayun.com/?access-token=server-side&projectId=' . $projectId . '&filename=' . $fileName;
        },
        'import_api' => function ($projectId) {
            return 'http://contact2.wpcn.dev/rest/v2/' . $projectId . '/users';
        }
    ],
    'beta' => [
        'file_path' => function ($projectId, $taskId) {
            return "data/" . $projectId . "/" . $taskId . ".csv";
        },
        'json_path' => function ($projectId, $taskId) {
            return "data/" . $projectId . "/" . $taskId . "/";
        },
        'upload_uri' => function () {
            return "http://10.10.62.202:8776";
        },
        'downloader_uri' => function ($accessToken, $projectId, $fileName) {
            return $fileName;//'http://10.10.62.202:8776/?access-token=server-side&projectId=' . $projectId . '&filename=' . $fileName;
        },
        'import_api' => function ($projectId) {
            return 'http://10.10.49.130:9089/rest/v2/' . $projectId . '/users';
        }
    ],
    'prod' => [
        'file_path' => function ($projectId, $taskId) {
            return "data/" . $projectId . "/" . $taskId . ".csv";
        },
        'json_path' => function ($projectId, $taskId) {
            return "data/" . $projectId . "/" . $taskId . "/";
        },
        'upload_uri' => function () {
            return "http://10.10.62.202:8776";
        },
        'downloader_uri' => function ($accessToken, $projectId, $fileName) {
            return $fileName;//'http://10.10.62.202:8776/?access-token=server-side&projectId=' . $projectId . '&filename=' . $fileName;
        },
        'import_api' => function ($projectId) {
            return 'http://10.10.49.130:9089/rest/v2/' . $projectId . '/users';
        }
    ]
];

return $_config[ENV];
