<?php

$_config = [
    'local' => [
        'file_path' => function ($projectId, $taskId) {
            return "data/" . $projectId . "/" . $taskId . "/";
        },
        'json_path' => function ($projectId, $taskId) {
            return "data/" . $projectId . "/" . $taskId . "/";
        },
        'upload_uri' => function ($token) {
            return "http://upload.dev.dmayun.com" . "/index.php?access-token=" . $token; //return "http://upload.wpcn.dev";
        },
        'downloader_uri' => function ( $projectId, $fileName) {
            return 'http://upload.dev.dmayun.com?projectId=' . $projectId . '&filename=' . $fileName;
        },
        'import_api' => function ($projectId) {
            return 'http://contact2.wpcn.dev/rest/v2/' . $projectId . '/users';
        },
        'download' => function ($params) {
            $url = "http://task2.wpcn.dev/rest/v2/downloads/" . $params['download_id'] . "?projectId=" . $params['projectId'];
            return $url;
        }
    ],
    'dev' => [
        'file_path' => function ($projectId, $taskId) {
            return "data/" . $projectId . "/" . $taskId . "/";
        },
        'json_path' => function ($projectId, $taskId) {
            return "data/" . $projectId . "/" . $taskId . "/";
        },
        'upload_uri' => function ($token) {
            return "http://upload2.dev.dmayun.com" . "/index.php?access-token=" . $token; //return "http://upload.wpcn.dev";
        },
        'downloader_uri' => function ( $projectId, $fileName) {
            return $fileName;
        },
        'import_api' => function ($projectId) {
            return 'http://contact2.wpcn.dev/rest/v2/' . $projectId . '/users';
        },
        'download' => function ($params) {
            $url = "http://task2.wpcn.dev/rest/v2/downloads/" . $params['download_id'] . "?projectId=" . $params['projectId'];
            return $url;
        }
    ],
    'beta' => [
        'file_path' => function ($projectId, $taskId) {
            return "data/" . $projectId . "/" . $taskId . "/";
        },
        'json_path' => function ($projectId, $taskId) {
            return "data/" . $projectId . "/" . $taskId . "/";
        },
        'upload_uri' => function ($token) {
            return "http://10.10.2.194:8776" . "/index.php?access-token=" . $token; //return "http://upload.wpcn.dev";
        },
        'downloader_uri' => function ( $projectId, $fileName) {
            return $fileName;
        },
        'import_api' => function ($projectId) {
            return 'http://10.10.49.130:9089/rest/v2/' . $projectId . '/users';
        },
        'download' => function ($params) {
            $url = "http://10.10.49.130:9099/rest/v2/downloads/" . $params['download_id'] . "?projectId=" . $params['projectId'];
            return $url;
        }
    ],
    'prod' => [
        'file_path' => function ($projectId, $taskId) {
            return "data/" . $projectId . "/" . $taskId . "/";
        },
        'json_path' => function ($projectId, $taskId) {
            return "data/" . $projectId . "/" . $taskId . "/";
        },
        'upload_uri' => function ($token) {
            return "http://10.10.2.194:8776" . "/index.php?access-token=" . $token; //return "http://upload.wpcn.dev";
        },
        'downloader_uri' => function ( $projectId, $fileName) {
            return $fileName;
        },
        'import_api' => function ($projectId) {
            return 'http://10.10.49.130:9089/rest/v2/' . $projectId . '/users';
        },
        'download' => function ($params) {
            $url = "http://10.10.49.130:9099/rest/v2/downloads/" . $params['download_id'] . "?projectId=" . $params['projectId'];
            return $url;
        }
    ]
];

return $_config[ENV];
