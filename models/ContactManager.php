<?php

namespace models;

use util\configManager;
use util\log;

class ContactManager
{
    // 环境配置信息
    private $configEnv;

    /**
     * 联系人数据库句柄
     * @var \PDO
     */
    private $dbhContact;

    // 项目Id
    private $projectId;

    /**
     * 构造函数
     *
     * 定义联系人数据库句柄
     *
     * @param array $connConfig 数据库配置
     * @param string $projectId 项目Id
     */
    public function __construct($projectId = null)
    {
        $this->projectId = $projectId;

        // 读取环境信息
        $connContact = configManager::loadSysConfig('conn-contact');
        $restConfig = configManager::loadSysConfig('rest');
        $this->configEnv = [
            'rest' => $restConfig[ENV],
            'mysql' => [
                'contact' => $connContact[ENV],
            ],
        ];

        $this->dbhContact = new \PDO($this->configEnv['mysql']['contact']['dsn'], $this->configEnv['mysql']['contact']['username'], $this->configEnv['mysql']['contact']['password']);
    }

    /**
     * 得到字段定义
     *
     * @param  string $projectId 项目Id
     * @param  string $columnName 列名
     * @return array 字段内容, false表示无数据
     */
    public function getFieldDefination($columnName)
    {
        $sql = "SELECT * FROM `fields` WHERE project_name = :project_name AND name = :name LIMIT 0, 1";
        $sth = $this->dbhContact->prepare($sql);
        $sth->bindValue(':project_name', $this->projectId, \PDO::PARAM_STR);
        $sth->bindValue(':name', $columnName, \PDO::PARAM_STR);
        $sth->execute();
        $result = $sth->fetch(\PDO::FETCH_ASSOC);
        return $result;
    }

    /**
     * 加全局锁
     *
     * @param string $lockId 锁Id global|field
     * @param string $lockReason 锁原因
     * @return bool 是否成功
     */
    public function addGlobalLock($lockId, $lockReason)
    {
        $sql = "INSERT INTO `global_lock` (project_name, lock_id, lock_status, lock_time, lock_reason) VALUES (:project_name, :lock_id, 1, NOW(), :lock_reason) ON DUPLICATE KEY UPDATE lock_status = 1, lock_time = NOW(), lock_reason = :lock_reason";
        $sth = $this->dbhContact->prepare($sql);
        $sth->bindValue(':project_name', $this->projectId, \PDO::PARAM_STR);
        $sth->bindValue(':lock_id', $lockId, \PDO::PARAM_STR);
        $sth->bindValue(':lock_reason', $lockReason, \PDO::PARAM_STR);
        return $sth->execute();
    }

    /**
     * 去掉全局锁
     * @param string $lockId 锁Id global|field
     * @return bool 是否成功
     */
    public function removeGlobalLock($lockId)
    {}

    /**
     * 得到duplicate option的值 (去重标准)
     *
     * @return string duplicate option值 | false表示查询无结果
     */
    public function getDuplicateOption()
    {
        $sql = "SELECT standard FROM duplicate_option WHERE project_name = :project_name";
        $sth = $this->dbhContact->prepare($sql);
        $sth->bindValue(':project_name', $this->projectId, \PDO::PARAM_STR);
        $sth->execute();
        return $sth->fetchColumn();
    }

    /**
     * 得到自定义字段列表
     * @param  array $exclusives 排除的字段名
     *
     * @return 结果
     */
    public function getCustomFields(array $exclusives = [])
    {
        if (count($exclusives) > 0) {
            $exclusiveQuotes = [];
            foreach ($exclusives as $exclusive) {
                $exclusiveQuotes[] = $this->dbhContact->quote($exclusive, \PDO::PARAM_STR);
            }
            $sql = "SELECT * FROM fields WHERE project_name = :project_name AND name NOT IN (" . implode(', ', $exclusiveQuotes) . ")";
        } else {
            $sql = "SELECT * FROM fields WHERE project_name = :project_name";
        }
        $sth = $this->dbhContact->prepare($sql);
        $sth->bindValue(':project_name', $this->projectId, \PDO::PARAM_STR);
        $sth->execute();
        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * 得到自定义字段列表
     * @return array 结果
     */
    public function getCustomFieldNames()
    {
        $url = $this->configEnv['rest']['contact'] . $this->projectId . '/fields';
        $response = \Httpful\Request::get($url)->expectsJson()->send();
        $return = [];

        if (isset($response->body) && isset($response->body->success) && $response->body->success == true) {
            foreach ($response->body->data->list as $listItem) {
                $return[] = $listItem->name;
            }
        } else {
            throw new \Exception("获取自定义字段列表错误: " .$url );
        }

        return $return;
    }

    /**
     * 得到联系人总数
     * @param  string $type email或mobile
     * @param  array $listIds [<description>]
     * @param  array $filterIds [<description>]
     * @return number 联系人总数
     *
     * TODO: 此方法可与mobile重用，加个参数标识email或moble。
     */
    public function getTotalCount($type, array $listIds, array $filterIds = [])
    {
        $url = $this->configEnv['rest']['contact'] . $this->projectId . '/users/total-count?show_' . $type . '_user=1&show_available=1&list_id=' . implode(",", $listIds);
        if ($filterIds) {
            $url .= '&filter_ids=' . implode(",", $filterIds);
        }
        $response = \Httpful\Request::get($url)->expectsJson()->send();

        if (!isset($response->body) || !isset($response->body->success) || $response->body->success == false) {
            throw new \Exception("获取联系人总数错误: " );
        }

        log::write('get-data', '获取联系人总数: ' . $url . ', result: ' . json_encode($response->body));

        return $response->body->data->total_count;
    }

    /**
     * 得到联系人数据
     * @param  number $page 页数
     * @return array        联系人数据结果
     *
     * TODO: 此方法可与mobile重用，加个参数标识email或moble。
     */
    public function getUsers($page, $pageSize, $type, array $listIds, array $filterIds = [], $minId = -1)
    {
        $url = $this->configEnv['rest']['contact'] . $this->projectId . '/users?ignore_sort=1&show_fields=1&show_' . $type . '_user=1&show_available=1&page_size=' . $pageSize . '&page=' . $page . '&list_id=' . implode(",", $listIds);
        if ($minId != -1) {
            $url .= '&min_id=' . $minId;
        }
        if ($filterIds) {
            $url .= '&filter_ids=' . implode(",", $filterIds);
        }
        $time_start = microtime(true);
        $response = \Httpful\Request::get($url)->expectsJson()->send();

        if (!isset($response->body) || !isset($response->body->success) || $response->body->success == false) {
            throw new \Exception("获取联系人用户内容错误: " );
        }

        log::write('get-data', '耗时' . (microtime(true) - $time_start) . '秒, 获取联系人: ' . $url);

        return $response->body->data->users;
    }

    /**
     * 复制一个表的结构到新的表
     * @param  [type] $oldTable [description]
     * @param  [type] $newTable [description]
     * @return [type]           [description]
     */
    public function copyTableSchema($oldTable, $newTable)
    {

    }
}
