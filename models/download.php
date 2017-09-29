<?php

namespace models;

use Httpful\Httpful;
use util\log;
// 下载类
class download {
    // 保存下载链接
    public static function saveResult($uriHandle, $projectId, $downloadId, $file_url_lists, $files_total_size) {
        $data = [
            'file_url_lists' => $file_url_lists,
            'files_total_size' => $files_total_size,
            'status' => 'finished'
        ];


        $request = \Httpful\Request::put($uriHandle([
                    'projectId' => $projectId,
                    'download_id' => $downloadId
                ]))
                ->sendsJson()
                ->body(json_encode($data))
                ->expectsJson()
                ->send();
        if ($request->body->success) {
            log::write('save-result', 'export finished, save the file list, downloadId is ' . $downloadId);
        } else {
            throw new \Exception('save-result, downloadId is ' . $downloadId . ', message :' . $request->body->data->message, 1);
        }
    }

}
