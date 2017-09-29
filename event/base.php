<?php
namespace event;

use event\Ievent;
// 事件基类
class base implements Ievent
{
	private $source;
    // 获取名称
	function getName()
	{
		return NULL;
	}
    // 运行
    // data是数据 source是事件源
    function run($data, $source = null)
    {
        throw new Exception("no access", 1);
    }
    // 设置源
    function setSource($source)
    {
    	$this->source = $source;
    }
    // 获取源
    function getSource(){
    	return $this->source;
    }
}