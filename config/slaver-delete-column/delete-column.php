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
            return 'http://upload.*.dev/?access-token=aac9cbaf5ece422fbe894fb0804db158&projectId=' . $projectId . '&filename=' . $fileName;
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
            return "http://upload.*.lc";
        },
        'downloader_uri' => function ($accessToken, $projectId, $fileName) {
            return 'http://upload.*.dev/?access-token=aac9cbaf5ece422fbe894fb0804db158&projectId=' . $projectId . '&filename=' . $fileName;
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
            return "http://upload.*.lc";
        },
        'downloader_uri' => function ($accessToken, $projectId, $fileName) {
            return 'http://upload.*.dev/?access-token=aac9cbaf5ece422fbe894fb0804db158&projectId=' . $projectId . '&filename=' . $fileName;
        },
        'import_api' => function ($projectId) {
            return 'http://contact2.*.dev/rest/v2/' . $projectId . '/users';
        }
    ]
];

return $_config[ENV];