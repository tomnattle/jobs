<?php
namespace schedule;

use event\IEvent;
use event\base;
use util\log;
// 活动结束
class taskFinish extends base
{
	const NAME = 'task-finish';

	function getName()
	{
		return self::NAME;
	}

    function run($data, $source = null)
    {
    	$task = $data['task'];
    	log::write(self::NAME, $task->uuid, log::EVENT);

    	
    	/**
			
			

    	**/
    }
}