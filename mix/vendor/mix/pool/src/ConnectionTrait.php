<?php

namespace Mix\Pool;

use Mix\Core\Coroutine;

/**
 * Trait ConnectionTrait
 * @package Mix\Pool
 * @author liu,jian <coder.keda@gmail.com>
 */
trait ConnectionTrait
{

    /**
     * @var \Mix\Pool\ConnectionPoolInterface
     */
    public $connectionPool;

    /**
     * 初始化事件
     */
    public function onInitialize()
    {
        parent::onInitialize(); // TODO: Change the autogenerated stub
        // 启用协程钩子
        Coroutine::enableHook();
    }

    /**
     * 析构事件
     */
    public function onDestruct()
    {
        parent::onDestruct(); // TODO: Change the autogenerated stub
        // 丢弃连接
        if (isset($this->connectionPool)) {
            $this->connectionPool->discard($this);
        }
    }

    /**
     * 释放连接
     * @return bool
     */
    public function release()
    {
        if (isset($this->connectionPool)) {
            return $this->connectionPool->release($this);
        }
        return false;
    }

}
