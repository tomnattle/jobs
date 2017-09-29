<?php

namespace models;

use models\helper\Helper;
use models\helper\EmailHelper;
use models\helper\MysqlHelper;
use util\configManager;
use util\log;

class smsSplitter extends MailSplitter
{
    public function __construct(array $configData, \executer\base $caller)
    {
        parent::__construct($configData, $caller);
    }
    /**
     * 执行
     * @return boolean 是否成功
     */
    public function run()
    {
        $this->reset();

        // 锁定
        $this->beginLock();

        // 得到符合条件的联系人总数
        $contactTotalCount = $this->contactManager->getTotalCount('mobile', $this->configData['config']['list'], $this->configData['config']['filters']);
        print "Contact total count: $contactTotalCount\n";
        $this->caller->updateProcessRate($this->caller->getProcessRate('start'));

        // 分页拉取用户数据并且生成邮件
        $retryTimes = [5, 60, 300];
        $contactPageCount = ceil($contactTotalCount / $this->contactPageSize);
        $minId = 0;
        for ($page = 1; $page <= $contactPageCount; $page ++) {

            for ($u = 0; $u <= count($retryTimes); $u++) {
                try {
                    $contactUsers = $this->contactManager->getUsers(1, $this->contactPageSize, 'mobile', $this->configData['config']['list'], $this->configData['config']['filters'], $minId); // 拉取一批用户数据
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

            if (count($contactUsers) > 0) {
                $minId = (int) $contactUsers[count($contactUsers) - 1]->id;
            }

            $this->bulkInsertMongodb($contactUsers);

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
            'batch_total_count' => 0,
        ]);

        return true;
    }

}