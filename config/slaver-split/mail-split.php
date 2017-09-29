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
            return "http://upload.*.lc";
        },
        'downloader_uri' => function ($accessToken, $projectId, $fileName) {
            return 'http://upload.dev.*.com/?access-token=server-side&projectId=' . $projectId . '&filename=' . $fileName;
        },
        'import_api' => function ($projectId) {
            return 'http://contact2.*.dev/rest/v2/' . $projectId . '/users';
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
            return "http://upload2.*.dev";
        },
        'downloader_uri' => function ($accessToken, $projectId, $fileName) {
            return $fileName;//'http://upload2.dev.*.com/?access-token=server-side&projectId=' . $projectId . '&filename=' . $fileName;
        },
        'import_api' => function ($projectId) {
            return 'http://contact2.*.dev/rest/v2/' . $projectId . '/users';
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
            return "http://*.*.62.202:8776";
        },
        'downloader_uri' => function ($accessToken, $projectId, $fileName) {
            return $fileName;//'http://*.*.62.202:8776/?access-token=server-side&projectId=' . $projectId . '&filename=' . $fileName;
        },
        'import_api' => function ($projectId) {
            return 'http://*.*.49.130:9089/rest/v2/' . $projectId . '/users';
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
            return "http://*.*.62.202:8776";
        },
        'downloader_uri' => function ($accessToken, $projectId, $fileName) {
            return $fileName;//'http://*.*.62.202:8776/?access-token=server-side&projectId=' . $projectId . '&filename=' . $fileName;
        },
        'import_api' => function ($projectId) {
            return 'http://*.*.49.130:9089/rest/v2/' . $projectId . '/users';
        }
    ]
];

return $_config[ENV];
