<?php

namespace models;

use models\helper\Helper;
use models\helper\EmailHelper;
use models\helper\MysqlHelper;
use util\configManager;
use util\log;
use conn\mongo;

/**
 * 邮件内容生成器
 *
 * 1)获取素材内容模板
 * 2)替换模板内所有链接为短链
 * 如：baidu.com替换为{$link.a245b}
 * 3)分批获取联系人列表（每4000人批次+1）
 * 4)为每个联系人生成模板，并且替换以下链接
 * ①替换mail_subject
 * ②替换unsubscribe
 * ③替换weblink
 * ④替换自定义字段
 * ⑤替换短链（用于点击统计）
 * 5)邮件末尾埋点（用于打开统计）
 * 6)生成邮件原始数据
 * 7)保存邮件备份
 * 8)锁的处理
 *
 * https://github.com/nategood/httpful
 *
 * 素材内容读取，联系人读取（分批），短链内容读取，埋点代码模板，邮件生成处理（替换加埋点），进度回传，锁的处理
 */
class MailSplitter
{
    // 环境配置信息
    protected $configEnv;

    // 任务配置
    protected $configData;

    // 调用者
    protected $caller;

    // 项目Id
    protected $projectId;

    // 任务Id;
    protected $taskId;

    // 联系人抓取的pagesize
    protected $contactPageSize = 4000;

    // 每批次包含的page数
    protected $contactBatchPageCount = 1;

    // 每批次提交的mongodb记录数
    protected $mongodbBatchSize = 4000;

    // 自定义字段列表
    protected $customFieldNames = [];

    // 短链列表
    protected $shortLinks = [];

    // 插入task数据的mongoclient和mongocollection对象
    protected $mongoClientTask;

    protected $mongoCollectionTask;

    // 任务日志管理器实例
    protected $taskLogManager;

    // 联系人实例
    protected $contactManager;

    /**
     * 构造函数
     * @param array $configData 数据配置
     * @param \executer\base $caller     调用者
     *
     * TODO: 此方法可与mobile重用。
     */
    public function __construct(array $configData, \executer\base $caller)
    {
        $this->configData = $configData;
        $this->caller = $caller;

        if (!isset($this->configData['config']['flt_id'])) {
            $this->configData['config']['filters'] = [];
        } else {
            $this->configData['config']['filters'] = explode(',', $this->configData['config']['flt_id']);
        }

        // 读取环境信息
        $restConfig = configManager::loadSysConfig('rest');
        $mongodbTaskConfig = configManager::loadSysConfig('mongo');
        $this->configEnv = [
            'rest' => $restConfig[ENV],
            'mongodb' => [
                'task' => $mongodbTaskConfig[ENV],
            ],
        ];

        if (!isset($configData['projectId'])) {
            throw new \Exception('配置内容中projectId未定义');
        }
        $this->projectId = $configData['projectId'];
        $this->taskId = (int) $this->configData['id'];

        if (isset($this->configEnv['mongodb']['task']['username']) && isset($this->configEnv['mongodb']['task']['password'])) {
            $this->mongoClientTask = new \MongoClient(
                "mongodb://" . $this->configEnv['mongodb']['task']['username'] . ":" . $this->configEnv['mongodb']['task']['password'] . "@"
                . $this->configEnv['mongodb']['task']['host'] . ":" . $this->configEnv['mongodb']['task']['port'] . "/" . $this->configEnv['mongodb']['task']['database']
                , ['socketTimeoutMS' => 30 * 60 * 1000]
            );
        } else {
            $this->mongoClientTask = new \MongoClient(
                "mongodb://" . $this->configEnv['mongodb']['task']['host'] . ":" . $this->configEnv['mongodb']['task']['port'] . "/" . $this->configEnv['mongodb']['task']['database']
                , ['socketTimeoutMS' => 30 * 60 * 1000]
            );
        }
        // $this->mongoClientTask =  mongo::getConn();

        $databaseName = $this->configEnv['mongodb']['task']['database'];
        $collectionName = $this->projectId . '_' . $this->taskId;
        $this->mongoCollectionTask = $this->mongoClientTask->$databaseName->$collectionName;

        $this->taskLogManager = new TaskLogManager($this->projectId, $this->taskId);
        $this->contactManager = new ContactManager($this->projectId);
    }

    /**
     * 执行
     * @return boolean 是否成功
     */
    public function run()
    {
        // 任务数据状态重置
        $this->reset();

        // 锁定
        $this->beginLock();

        // 读取自定义字段列表
        $this->customFieldNames = $this->contactManager->getCustomFieldNames();

        // 读取素材内容
        $templateContent = $this->getTemplateContent();

        // 替换模板内所有链接为短链 如：baidu.com替换为{$link.a245b}
        $templateContent = $this->replaceTemplateShortLinks($templateContent);

        // 存储template内容至mongodb
        $this->saveTemplateContent($templateContent);

        // 得到符合条件的联系人总数
        $contactTotalCount = $this->contactManager->getTotalCount('email', $this->configData['config']['list'], $this->configData['config']['filters']);
        $this->caller->updateProcessRate($this->caller->getProcessRate('start'));

        // 分页拉取用户数据并且生成邮件
        $retryTimes = [5, 60, 300];
        $contactPageCount = ceil($contactTotalCount / $this->contactPageSize);
        $minId = 0;
        for ($page = 1; $page <= $contactPageCount; $page ++) {
            $batchId = ceil($page / $this->contactBatchPageCount); // 当前批次Id

            for ($u = 0; $u <= count($retryTimes); $u++) {
                try {
                    $contactUsers = $this->contactManager->getUsers(1, $this->contactPageSize, 'email', $this->configData['config']['list'], $this->configData['config']['filters'], $minId); // 拉取一批用户数据
                    break;
                } catch (\Exception $e) {
                    if ($u == count($retryTimes)) {
                        log::write('retry', '[' . $this->taskId . '] 重试' . $u . '失败。失败原因: ' . $e->getMessage());
                        throw new \Exception($e->getMessage());
                    }
                    log::write('retry', '[' . $this->taskId . '] 第' . $u . '次尝试失败，等待重试。失败原因: ' . $e->getMessage());
                    sleep($retryTimes[$u]);
                }
            }

            $time_start = microtime(true);
            if (count($contactUsers) > 0) {
                $minId = (int) $contactUsers[count($contactUsers) - 1]->id;
            }
            $mongodbMailContents = []; // 初始化待写入mongodb的数据集合
            foreach ($contactUsers as $contactUser) {
                // id必须为数字类型
                $contactUser->id = (int) $contactUser->id;
                // 得到邮件数据生成内容，并且附加批次Id、发送状态、事件列表等信息。
                $mongodbMailContents[] = array_merge(
                    // $this->dealUserMail($contactUser, $templateContent),
                    $this->dealUserMail($contactUser),
                    ['batch_id' => $batchId, 'events' => []]
                );
            }
            log::write('deal-user-mail', '[' . $this->taskId . '] 耗时' . (microtime(true) - $time_start) . '秒, 处理自定义内容。');

            // 将数据批量写入mongodb，将批次信息写入mysql。
            $this->bulkInsertMongodb($mongodbMailContents);
            if ($page % $this->contactBatchPageCount == 0 || $page == $contactPageCount) {
                $this->createBatchRecord($batchId);
            }
            $time_start = microtime(true);
            $percent = round($this->caller->getProcessRate('start') + ($this->caller->getProcessRate('finish') - $this->caller->getProcessRate('start')) * $page / $contactPageCount);
            $this->caller->updateProcessRate($percent);
            log::write('update-process', '[' . $this->taskId . '] 耗时' . (microtime(true) - $time_start) . '秒, 更新任务进度: ' . $percent . '%。');
        }

        // 解锁
        $this->endLock();

        // 记录结果
        $this->caller->updateProcessRate($this->caller->getProcessRate('finish'));
        $this->caller->setResult([
            'contact_total_count' => $contactTotalCount,
            'batch_total_count' => isset($batchId) ? $batchId : 0,
        ]);

        // 更新task_email信息
        $this->updateTaskEmail($contactTotalCount);

        return true;
    }

    /**
     * 任务数据状态重置
     * @return void
     */
    protected function reset()
    {
        // 清除mongodb原集合
        $this->mongoCollectionTask->drop();
        $this->mongoCollectionTask->createIndex(['id' => 1], ['unique' => false]); // TODO: 此处unique应为true，但暂时无法解决偶然出现的id重复问题。未来解决方法：分页读记录时按id正排序，每次记录最后一个id下次查询大于这个id的记录。
        $this->mongoCollectionTask->createIndex(['batch_id' => 1], ['unique' => false]);
        $databaseName = $this->configEnv['mongodb']['task']['database'];
        $collectionName = $this->projectId . '_' . $this->taskId . '_events';
        $this->mongoClientTask->$databaseName->$collectionName->drop();
        $this->mongoClientTask->$databaseName->$collectionName->createIndex(['user_id' => 1], ['unique' => false]);

        // 清除batch表原纪录
        $url = $this->configEnv['rest']['task'] . 'batchs/task/' . $this->configData['uuid'];
        \Httpful\Request::delete($url)->send();
    }

    /**
     * 锁定
     * TODO: everything~
     *
     * TODO: 此方法可与mobile重用。
     */
    protected function beginLock()
    {
        // $data = [
        //     'entity_type' => 'list',
        //     'entity_ids' => $this->configData['config']['list'],
        //     'lock_reason' => '发送任务预处理，task id: ' . $this->taskId,
        //     'operation' => 'split',
        //     'operator_key' => 'task:' . $this->taskId,
        // ];
        // $json = json_encode($data);
        // $url = $this->configEnv['rest']['contact'] . $this->projectId . '/lock';
        // $response = \Httpful\Request::put($url)->sendsJson()->body($json)->send();
        // if (isset($response->body)) {
        //     log::write('lock-contact', '锁定联系人分组: ' . $url . ', data: ' . $json . ', result: ' . json_encode($response->body));
        // }
    }

    /**
     * 解锁
     * TODO: everything~
     *
     * TODO: 此方法可与mobile重用。
     */
    public function endLock()
    {
        $data = [
            'entity_type' => 'list',
            'entity_ids' => $this->configData['config']['list'],
            'operation' => 'split',
            'operator_key' => 'task:' . $this->taskId,
        ];
        $json = json_encode($data);
        $url = $this->configEnv['rest']['contact'] . $this->projectId . '/unlock';
        $response = \Httpful\Request::put($url)->sendsJson()->body($json)->send();
        if (isset($response->body)) {
            log::write('unlock-contact', '[' . $this->taskId . '] 解锁联系人分组: ' . $url . ', data: ' . $json . ', result: ' . json_encode($response->body));
        }
    }

    /**
     * 得到素材内容
     * @return string 素材原始内容
     *
     * TODO: 此方法可与mobile重用。
     */
    protected function getTemplateContent()
    {
        // 调用素材rest服务
        $url = $this->configEnv['rest']['content'] . $this->projectId . '/template/' . $this->configData['config']['template_id'];
        $response = \Httpful\Request::get($url)->expectsJson()->send();

        if (isset($response->body) && isset($response->body->success) && $response->body->success == true) {
            return $response->body->data->template->content;
        } else {
            throw new \Exception("获取素材内容模板错误: " . $response);
        }
    }

    /**
     * 替换模板内所有链接为短链 如：baidu.com替换为{$link.a245b}
     * @param  string $content 输入内容
     * @return string          替换后的输出内容
     */
    protected function replaceTemplateShortLinks($content)
    {
        preg_match_all("/href=[\'\"]?([^\'\" ]+).*?>/", $content, $linkList);
        $linkList = $linkList[1];
        foreach ($linkList as $key => $url) {
            // 去除网页内部链接
            if (in_array($url, ["#", ""])) {
                unset($linkList[$key]);
            }
        }
        $linkList = array_values($linkList);
        if ($linkList) {
            $data = [
                'urls' => $linkList
            ];
            $json = json_encode($data);
            $url = $this->configEnv['rest']['trackEmail'] . 'index.php?type=geturl';

            $response = \Httpful\Request::post($url)->sendsJson()->body($json)->send();
            log::write('get-short-url', '[' . $this->taskId . '] ' . $url . ": " . $json);

            if ($response && $response->code == '200') {
                if ($response->body->success) {
                    $data = $response->body->data->short_ids;
                    if ($data) {
                        // $key原始链接 $shortLink是生成链接
                        foreach ($data as $key => $shortLink) {
                            $this->shortLinks[] = $shortLink;
                            $str = str_replace("/", "\/", $linkList[$key]);
                            $str = str_replace("?", "\?", $str);
                            $str = str_replace("(", "\(", $str);
                            $str = str_replace(")", "\)", $str);
                            $content = preg_replace("/href=[\'\"]" . $str . "[\'\"]/", "href='{link." . $shortLink . "}'", $content);
                        }
                    }
                }
            } else {
                throw new \Exception('get short url fail.');
            }
        }

        return $content;
    }

    /**
     * 存储template内容至mongodb
     * @param  string $templateContent 邮件内容
     */
    protected function saveTemplateContent($templateContent)
    {
        $databaseName = $this->configEnv['mongodb']['task']['database'];
        $collectionName = $this->projectId . '_send_mail_templates';
        $this->mongoClientTask->$databaseName->$collectionName->update(
            ['_id' => $this->taskId],
            ['content' => $templateContent, 'short_links' => $this->shortLinks, 'custom_field_names' => $this->customFieldNames],
            ['upsert' => true]
        );
    }

    /**
     * 处理用户邮件
     *
     * 6.0）mail subject自定义字段替换
     * 6)生成邮件原始数据
     * 7)保存邮件备份
     *
     * @param  stdClass $contactUser   待处理用户
     * @param  string $templateContent 素材内容 (已废弃)
     * @return array                   等待插入mongodb内容
     */
    // protected function dealUserMail(\stdClass $contactUser, $templateContent)
    protected function dealUserMail(\stdClass $contactUser)
    {
        // $content = $this->getUserMailContent($contactUser, $templateContent);

        $return = [
            'id' => $contactUser->id,
            'email' => $contactUser->email,
            'mobile' => $contactUser->mobile,
            'to_name' => isset($contactUser->name) ? $contactUser->name : EmailHelper::getNamePart($contactUser->email),
            'to_address' => $contactUser->email,
            'from_name' => $this->configData['config']['from_name'],
            'from_address' => $this->configData['config']['from_address'],
            'mail_subject' => $this->_replaceCustomFields($contactUser, $this->configData['config']['subject']),
            'to_domain' => EmailHelper::getDomainPart($contactUser->email),
            // 'content' => $content,
            'weblink' => $this->_createWebLink($contactUser),
            'open_link' => $this->_getTrackOpenDotImageUrl($contactUser),
            'unsubscribe_link' => $this->_getUnsubscribeLink($contactUser),
            'short_links' => $this->_getShortLinks($contactUser),
            'fields' => [],
        ];

        // 把来自contact接口自定义字段和id信息也加到mongodb里
        foreach ($contactUser as $key => $value) {
            if (in_array($key, $this->customFieldNames)) {
                $return['fields'][$key] = $value;
            }
        }

        return $return;
    }

    /**
     * 生成联系人个性化邮件内容
     *
     * 4)为每个联系人生成模板，并且替换以下链接
     * ②替换unsubscribe
     * ③替换weblink
     * ④替换自定义字段
     * ⑤替换短链（用于点击统计）
     * 5)邮件末尾埋点（用于打开统计）
     *
     * @param  stdClass $contactUser   待处理用户
     * @param  string $templateContent 素材内容
     * @return string                  处理后的邮件内容
     */
    protected function getUserMailContent(\stdClass $contactUser, $templateContent)
    {
        $content = $templateContent;

        $content = $this->_replaceUnsubscribeLink($contactUser, $content);
        $content = $this->_replaceWebLink($contactUser, $content);
        $content = $this->_replaceCustomFields($contactUser, $content);
        $content = $this->_replaceShortLinks($contactUser, $content);
        $content .= '<img style="height:0px;weight:0px;" src="' . $this->_getTrackOpenDotImageUrl($contactUser) . '" />';

        return $content;
    }

    /**
     * 批量插入mongo数据库
     * @param  array $mongodbMailContents 插入内容
     * @return boolean                  是否成功
     *
     * TODO: 此方法可与mobile重用，加个参数标识email或moble。
     */
    protected function bulkInsertMongodb($mongodbMailContents)
    {
        $chunks = array_chunk($mongodbMailContents, $this->mongodbBatchSize);
        foreach ($chunks as $chunk) {
            $time_start = microtime(true);
            $this->mongoCollectionTask->batchInsert($chunk);
            log::write('insert-data', '[' . $this->taskId . '] 耗时' . (microtime(true) - $time_start) . '秒, 写入' . count($chunk) . '条mongodb数据');
        }
    }

    /**
     * 生成batch记录
     * @param  number $page 页数
     * TODO: everything~
     */
    protected function createBatchRecord($batchNumber)
    {
        $url = $this->configEnv['rest']['task'] . 'batchs';
        $data = [
            "_index" => $batchNumber,
            "type" => "mail-send",
            "task_uuid" => $this->configData['uuid'],
            "_status" => "pending",
            "_plan_time" => MysqlHelper::getMysqlNowString(),
            "_process_rate" => $this->caller->getProcessRate('finish'),
        ];
        $json = json_encode($data);

        $time_start = microtime(true);
        $response = \Httpful\Request::post($url)->sendsJson()->body($json)->send();
        if ($response->body->success) {
        } else {
            throw new \Exception('创建batch任务失败');
        }
        log::write('insert-data', '[' . $this->taskId . '] 耗时' . (microtime(true) - $time_start) . '秒, 写入batch记录');
    }

    protected function updateTaskEmail($totalCount)
    {
        $data = [
            'total' => $totalCount,
        ];
        $json = json_encode($data);
        $url = $this->configEnv['rest']['task'] . 'task-email/' . $this->taskId;
        $response = \Httpful\Request::put($url)->sendsJson()->body($json)->send();
        if (isset($response->body)) {
            log::write('update-task-email', '[' . $this->taskId . '] 更新task_email信息: ' . $url . ', data: ' . $json . ', result: ' . json_encode($response->body));
        }
    }

    /**
     * 替换unsubscribe链接
     * @param  stdClass $contactUser 待处理用户
     * @param  string $content       原始内容
     * @return string                处理后的内容
     */
    private function _replaceUnsubscribeLink(\stdClass $contactUser, $content)
    {
        return str_replace('{$unsubscribe}', $this->_getUnsubscribeLink($contactUser), $content);
    }

    /**
     * 得到unsubscript链接
     * @param  stdClass $contactUser 待处理用户
     * @return string                退订url地址
     */
    private function _getUnsubscribeLink(\stdClass $contactUser)
    {
        $params = [
            'project_id' => $this->projectId,
            'task_id' => $this->taskId,
            'user_id' => $contactUser->id,
        ];

        return $this->_makeTrackEmailLink($params, 'u');
    }

    /**
     * 替换weblink链接
     * @param  stdClass $contactUser 待处理用户
     * @param  string $content       原始内容
     * @return string                处理后的内容
     */
    private function _replaceWebLink(\stdClass $contactUser, $content)
    {
        return str_replace('{$weblink}', $this->_createWebLink($contactUser), $content);
    }

    /**
     * 创建web link
     * @param  stdClass $contactUser 待处理用户
     * @return string                Web link内容
     */
    private function _createWebLink(\stdClass $contactUser)
    {
        $params = [
            'project_id' => $this->projectId,
            'task_id' => $this->taskId,
            'user_id' => $contactUser->id,
        ];

        return $this->_makeTrackEmailLink($params, 'w');
    }

    /**
     * 替换自定义字段
     * @param  stdClass $contactUser 待处理用户
     * @param  string $content       原始内容
     * @return string                处理后的内容
     *
     * TODO: 此方法可与mobile重用。
     */
    private function _replaceCustomFields(\stdClass $contactUser, $content)
    {
        foreach ($this->customFieldNames as $fieldName)
        {
            if (isset($contactUser->$fieldName)) {
                $content = str_replace('{$' . $fieldName . '}', $contactUser->$fieldName, $content);
            }
        }

        return $content;
    }

    /**
     * 替换短链接
     * @param  stdClass $contactUser 待处理用户
     * @param  string $content       原始内容
     * @return string                处理后的内容
     */
    private function _replaceShortLinks(\stdClass $contactUser, $content)
    {
        foreach ($this->shortLinks as $shortLink) {
            $content = str_replace('{link.' . $shortLink . '}', $this->_getShortLink($contactUser, $shortLink), $content);
        }

        return $content;
    }

    private function _getShortLinks(\stdClass $contactUser)
    {
        $return = [];

        foreach ($this->shortLinks as $shortLink) {
            $return[$shortLink] = $this->_getShortLink($contactUser, $shortLink);
        }

        return $return;
    }

    /**
     * 得到短链地址
     * @param  stdClass $contactUser 待处理用户
     * @param  string    $shortLink   短链字符串
     * @return string                 短链url地址
     */
    private function _getShortLink(\stdClass $contactUser, $shortLink)
    {
        $params = [
            'project_id' => $this->projectId,
            'task_id' => $this->taskId,
            'user_id' => $contactUser->id,
            'short' => $shortLink,
        ];

        return $this->_makeTrackEmailLink($params, 'c');
    }

    /**
     * 生成埋点图片Url
     * @param  \stdClass $contactUser 待处理用户
     * @return string                 图片Url地址
     */
    private function _getTrackOpenDotImageUrl(\stdClass $contactUser)
    {
        $params = [
            'project_id' => $this->projectId,
            'task_id' => $this->taskId,
            'user_id' => $contactUser->id,
        ];

        return $this->_makeTrackEmailLink($params, 'o');
    }

    /**
     * 生成track-email链接
     * @param  array  $params 拼接参数
     * @param  string $prefix rewrite前缀
     * @return string         链接结果
     */
    private function _makeTrackEmailLink(array $params, $prefix)
    {
        if (ENV == 'dev') {
            $domain = 'http://track-emails.dev.dmayun.com';
        } elseif (ENV == 'beta') {
            $domain = 'http://track-emails.beta.dmayun.com';
        } elseif (isset($this->configData['config']['domain'])) {
            $domain = rtrim($this->configData['config']['domain'], '/');
            if (substr($domain, 0, 4) != 'http') {
                $domain = 'http://' . $domain;
            }
        } else {
            $domain = rtrim($this->configEnv['rest']['trackEmail'], '/');
        }

        return $domain . '/' . $prefix . '/' . Helper::encrypt(json_encode($params));
    }
}