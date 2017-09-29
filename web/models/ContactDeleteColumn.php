<?php

namespace models;

class ContactDeleteColumn extends ContactManager
{
    // 删除行
    public function deleteUniqueColumn($columnName)
    {
        // 加全局锁
        if (!$this->addGlobalLock('global', 'delete column ' . $columnName)) {
            throw new Exception('Add global lock fail.');
        }

        // 根据旧的duplicate_option值得到新的duplicate_option
        if (!$duplicateStandard = $this->getDuplicateOption()) {
            throw new \Exception("Can not get duplicate standard.");
        }
        $duplicateStandards = explode(",", $duplicateStandard);
        $duplicateStandards = array_diff($duplicateStandards, [$columnName]);

        // 得到新表的所有自定义字段列表
        $customFields = $this->getCustomFields([$columnName]);

        // 创建新表 *_new 复制原有表结构 (user & user_list)
        if (!$this->copyTableSchema('user', 'user_new')) {
            throw new \Exception("Create new user table fail.");
        }

        // 添加/替换u1索引

        // 按新的去重规则循环复制数据 (overwrite or merge)，可每次处理1000条防止内存爆掉

        // 添加其他索引，如_updated (暂无)


        // 检查新表和原表数据的一致性，如根据u1索引SELECT COUNT DISTINCT ... 应得到相同的统计值

        // 重命名原表 *_origin

        // 重命名新表 *_new => *

        // 删除原表 *_origin

        // 删除fields下记录

        // 更新duplicate_option下记录

        // 取消全局锁
    }
}