<?php
namespace executer;

use slaver\type;
use util\configManager;
use util\log;
use models\ContactDeleteColumn as ContactDeleteColumn;

/**
 * 删除列子任务
 */
class deleteColumn extends base
{

    /**
     * 进度值阈值列表
     *
     * @var array
     */
    private $processRate = [
        'start' => 1,
        'download' => 30,
        'upload' => 99,
        'finish' => 100
    ];

    /**
     * 联系人模型
     *
     * @var \PDO
     */
    private $contactDeleteColumnModel;

    /**
     * 构造函数
     *
     * 定义联系人模型
     */
    public function __construct()
    {
        parent::$name = type::DELETE_COLUMN;
    }

    /**
     * 运行前
     */
    public function beforeRun()
    {
        // 读取配置文件
        $configData = $this->config->_data;

        // 初始化联系人模型
        $this->contactDeleteColumnModel = new ContactDeleteColumn($configData['projectId']);

        // 检测该字段是否已经被删除
        if (! $result = $this->contactDeleteColumnModel->getFieldDefination($configData['config']['column_name'])) {
            throw new \Exception("The field does not exists.");
        }

        // 检查该字段确实是唯一字段
        if ($result['is_unique'] != 1) {
            throw new \Exception("The field is not unique field.");
        }

        // 更新进度
        $this->updateProcessRate($this->processRate['start']);
    }

    /**
     * 运行
     */
    public function run()
    {
        // 读取配置文件
        $configData = $this->config->_data;

        // 得到需要删除的字段名称
        $deleteColumnName = $configData['config']['column_name'];

        // 记录开始时间
        $runStart = microtime(true);

        // 执行删除操作
        $this->contactDeleteColumnModel->deleteUniqueColumn($deleteColumnName);

        // 记录结束时间
        $runEnd = microtime(true);

        // 控制台输出结果及运行时间
        log::write('delete-column-run-complate', 'Time: ' . ($runEnd - $runStart) . ' seconds');

        // $this->updateProcessRate($this->processRate['download']);
        // $this->updateProcessRate($this->processRate['finish']);
    }

    /**
     * 运行后
     */
    public function afterRun()
    {
        $this->updateProcessRate($this->processRate['finish']);
    }
}

