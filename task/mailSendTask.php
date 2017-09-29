<?php
namespace task;

use api\task as apiTask;
use api\batch as apiBatch;
use api\job as apiJob;
use util\log;
use util\configManager;
use conn\mongo;
use models\TaskLogManager;


class mailSendTask extends base
{

    const NAME = "mail-send";

    private $mongo = null;

    public function __construct()
    {
        $this->setName(self::NAME);
    }
    // 修改slaver不运行时 仍然状态为processing的任务
    public function fix($slaverUuid = 0)
    {
        if($slaverUuid){
            $option = [
                'conditions' => "slaver_uuid = '" . $slaverUuid . "' and status = 'processing' "
            ];
            
            $jobs = apiJob::all($option);
            foreach ($jobs as $key => $job) {
                log::write('autofix', $this->name .', slaver[' . $slaverUuid . '],job[' . $job->uuid . '],task[' . $job->task_uuid . '],batch[' . $job->batch_uuid . '] _status processing to ready');
                apiBatch::update_all([
                        'set' => [
                            '_process_rate' => 0,
                            '_status' => 'ready',
                        ],
                        'conditions' => [
                            'uuid' => $job->batch_uuid,
                            '_status' => 'processing'
                        ]
                ]);

                $job->_finished = date('Y-m-d H:i:s');
                $job->status = 'abort';
                $job->abort_reason = 'slaver abort, manual Recycling ';
                $job->save();
            }
        }else{
           $option = [
            'conditions' => "type = '" . $this->name . "' AND _status = 'processing' "
            ];

            $batches = apiBatch::all($option);
            
            foreach ($batches as $batch) {
               $batch->_status = 'ready';
               $batch->save();
               log::write('autofix', self::NAME .', task [' . $batch->task_uuid . '][' . $batch->uuid . '] _status processing to ready');
            } 
        }
        
    }
    // 预检测 
    public function preCheck()
    {
        $this->startUpReadyTask();
        $this->startUpRestartTask();
    }
    // 开始准备好的活动
    public function startUpReadyTask()
    {

        $option = [
            'conditions' => "type = '" . $this->name . "' AND _status = 'ready' AND pre_status = 'finished' AND _plan_time<=now() "
        ];
        $result = apiTask::all($option);
        foreach ($result as $task) {
            try {
                $option = [
                        'conditions' => "task_uuid = '" . $task->uuid . "' AND _status = 'pending'"
                ];
                // 没有批次的活动 自动结束 并添加备注
                $batches = apiBatch::all($option);
                if(!$batches){
                    apiTask::update_all([
                        'set' => [
                            '_process_rate' => 100,
                            '_status' => 'finished',
                            '_started' => date('Y-m-d H:i:s'),
                            '_finished' => date('Y-m-d H:i:s'),
                            'abort_reason' => 'no batch means no body' 
                        ],
                        'conditions' => [
                            'uuid' => $task->uuid
                        ]
                    ]);
                    // 增加日志 记录活动自动结束
                    $taskLogManager = new TaskLogManager($task->projectid, $task->id);
                    $taskLogManager->addLog(TaskLogManager::ACT_EMPTY_LIST_FINISHED, []);

                    $taskLogManager->addMonitorLog(TaskLogManager::MONITOR_ACT_EMPTY_LIST_FINISHED, []);
                    continue;
                }

                apiBatch::update_all([
                    'set' => [
                        '_status' => 'ready'
                    ],
                    'conditions' => [
                        'task_uuid' => $task->uuid,
                        '_status' => 'pending'
                    ]
                ]);
            } catch (\Exception $e) {
                log::write('update-batch', 'task [' . $task->uuid . '] update batch failue,' . $e->getMessage());
                // 如果更新失败则需要处理
                continue;
            }
            
            apiTask::update_all([
                'set' => [
                    '_started' => date('Y-m-d H:i:s'),
                    '_status' => 'processing'
                ],
                'conditions' => [
                    'uuid' => $task->uuid
                ]
            ]);
            
            log::write('task-begin', 'task [' . $task->id . ',' . $task->uuid . '] begin');
            // 更新批次为ready
        }
    }
    // 开始重启的活动
    public function startUpRestartTask()
    {

        $option = [
            'conditions' => "type = '" . $this->name . "' AND _status = 'restart' "
        ];
        $result = apiTask::all($option);
        foreach ($result as $task) {
            
            try {
                apiBatch::update_all([
                    'set' => [
                        '_status' => 'ready'
                    ],
                    'conditions' => [
                        'task_uuid' => $task->uuid,
                        '_status' => 'pause'
                    ]
                ]);
            } catch (\Exception $e) {
                log::write('update-batch', 'pause to ready, task [' . $task->uuid . '] update batch failue,' . $e->getMessage());
                // 如果更新失败则update需要处理
                continue;
            }
            
            apiTask::update_all([
                'set' => [
                    '_status' => 'processing'
                ],
                'conditions' => [
                    'uuid' => $task->uuid
                ]
            ]);
            
            log::write('task-begin', 'task [' . $task->id . ',' . $task->uuid . '] restart');
            // 更新批次为ready
        }

    }

    // 检查是否完成
    public function checkTaskFinished($uuid)
    {
        //如果有进行中的 挂起 准备好 暂停的任务
        $option = [
            'conditions' => "task_uuid = '" . $uuid . "' AND _status in ('processing', 'pending', 'ready', 'pause')"
        ];
        
        $unfinishedBatch = apiBatch::all($option);
        if (! $unfinishedBatch) {
            log::write('checkTaskFinished', 'task [' . $uuid . '] has finished');
            return true;
        } else {
            log::write('checkTaskFinished', 'task [' . $uuid . '] has ' . count($unfinishedBatch) . ' batch undeal');
            return false;
        }
    }
    // 加载批次报告
    public function loadBatchInfoReport($uuid)
    {
        $data =[
            'total_batch_count' =>  count(apiBatch::all([
                'conditions' => "task_uuid = '" . $uuid . "' "
            ])),
            'pending_batch_count' => count(apiBatch::all([
                'conditions' => "task_uuid = '" . $uuid . "' AND _status = 'pending'"
            ])),
            'ready_batch_count' => count(apiBatch::all([
                'conditions' => "task_uuid = '" . $uuid . "' AND _status = 'ready'"
            ])),
            'processing_batch_count' => count(apiBatch::all([
                'conditions' => "task_uuid = '" . $uuid . "' AND _status = 'processing'"
            ])),
            'pause_batch_count' => count(apiBatch::all([
                'conditions' => "task_uuid = '" . $uuid . "' AND _status = 'pause'"
            ])),
            'abort_batch_count' => count(apiBatch::all([
                'conditions' => "task_uuid = '" . $uuid . "' AND _status = 'abort'"
            ])),
            'finished_batch_count' => count(apiBatch::all([
                'conditions' => "task_uuid = '" . $uuid . "' AND _status = 'finished'"
            ])),
            'cancel_batch_count' => count(apiBatch::all([
                'conditions' => "task_uuid = '" . $uuid . "' AND _status = 'cancel'"
            ]))
        ];
        return $data;
    }

    // 获取批次进度
    public function getFinishedBatchRate($uuid)
    {
        $option = [
            'conditions' => "task_uuid = '" . $uuid . "' AND _status = 'finished'"
        ];
        $finishedBatch = apiBatch::all($option);

        $option = [
            'conditions' => "task_uuid = '" . $uuid . "'"
        ];
        $allBatch = apiBatch::all($option);

        $finishedBatch_count = $finishedBatch ? count($finishedBatch) : 0;
        $allBatch_count = $allBatch ? count($allBatch) : 0;

        log::write('report', ' task [' . $uuid . '] ,finished rate: ' . $finishedBatch_count . '/' . $allBatch_count);

        return ceil(70 * ($finishedBatch_count / $allBatch_count));
    }

    // 获取一个准备好的活动
    public function loadReady()
    {
        $option = [
            'conditions' => "type = '" . $this->name . "' AND _status = 'ready' ",
            'order' => '_index asc',
            'limit' => 1
        ];
        

        $batch = apiBatch::all($option);
        
        if (! $batch)
            return [];
        $batch = ($batch[0]);
        $task = apiTask::first([
            'conditions' => "uuid = '" . $batch->task_uuid . "' "
        ]);
        
        $newTask = new \stdClass();
        $newTask->batch = $batch;
        $newTask->task = $task;
        return $newTask;
    }
    // 更新批次扣费
    public function updateBatchPaid($batch_uuid)
    {
         apiBatch::update_all([
            'set' => [
                'paid_status' => 1
            ],
            'conditions' => [
                'uuid' => $batch_uuid
            ]
        ]);
    }
    // 开始处理
    public function beginProcess($uuid)
    {
        $batch = apiBatch::first([
            'uuid' => $uuid
        ]);
        
        $batch->_status = 'processing';
        $batch->_created = date("Y-m-d H:i:s");
        $batch->save();
    }
    // 更新处理进度
    public function updateProcessRate($uuid, $rate)
    {
        $task = apiTask::find([
            'uuid' => $uuid
        ]);
        $task->_process_rate = $rate;
        $task->save();
    }
    // 更新批次进度
    public function updateBatchProcessRate($uuid, $rate)
    {
        $batch = apiBatch::find([
            'uuid' => $uuid
        ]);
        $batch->_process_rate = $rate;
        $batch->save();
    }
    
    // 批次结束
    public function finishedBatch($uuid)
    {
        $batch = apiBatch::first([
            'uuid' => $uuid
        ]);
        $batch->_status = 'finished';
        $batch->_finished = date("Y-m-d H:i:s");
        $batch->save();
    }
    // 终止
    public function abort($uuid, $abort_reason, $batch_uuid = 0)
    {
        //parent::abort($uuid, $abort_reason);
        $batch = apiBatch::find([
            'uuid' => $batch_uuid
        ]);
        $batch->_status = 'abort';
        $batch->abort_reson = $abort_reason;
        $batch->save();
    } 
    // 保存结果
    public function saveResult($uuid, $result)
    {
        $batch = apiBatch::find([
            'uuid' => $uuid
        ]);
        $batch->result = $result;
        $batch->save();
    }
    // 获取单个批次总数
    public function getCountBatch($projectId, $task_id, $batch_id)
    {
        $mongo = $this->getMongo();
        $count = $mongo->{$projectId . '_' . $task_id}->find(['batch_id' => $batch_id])->count();
        return $count;
    }
    // 暂停活动
    public function pauseTask($uuid,$reason)
    {
        // 暂停活动
        $task = apiTask::first([
            'conditions' => "uuid = '" . $uuid . "' "
        ]);
        $task->_status = 'pause';
        $task->pause_reason = $reason;
        $task->save();
        // 暂停批次
        apiBatch::update_all([
            'set' => [
                '_status' => 'pause'
            ],
            'conditions' => [
                'task_uuid' => $uuid,
                '_status' => 'ready',
            ]
        ]);
    }
    // 获取mongo
    public function getMongo(){
        if(!$this->mongo){
             //$mongoConfig = configManager::loadSysConfig('mongo');
            $this->mongo = mongo::getConn();
        }
        
        return $this->mongo;
    }
}


