<?php
/**
 * User: yongli
 * Date: 17/9/7
 * Time: 00:46
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace App\Core\Event;

/**
 * 时间接口类
 *
 * Interface EventInterface
 * @package App\Core\Event
 */
interface EventInterface
{
    // 读事件
    const EVENT_TYPE_READ = 1;
    // 写事件
    const EVENT_TYPE_WRITE = 2;
    // 永久性定时器事件
    const EVENT_TYPE_TIMER = 4;
    // 一次性定时器事件
    const EVENT_TYPE_TIMER_ONCE = 8;
    // 信号事件
    const EVENT_TYPE_SIGNAL = 16;

    /**
     * 初始化
     * EventInterface constructor.
     */
    public function __construct();

    /**
     * 添加事件
     *
     * @param $callback string|array 回调函数
     * @param $args     array 回调函数的参数
     * @param $resource resource|int 读写事件中表示socket资源,定时器任务中表示时间(int,秒),信号回调中表示信号(int)
     * @param $type     int 类型
     *
     * @return bool
     */
    public function add($callback, array $args, $resource, $type);

    /**
     * 删除指定的事件
     *
     * @param $resource resource|int 读写事件中表示socket资源,定时器任务中表示时间(int,秒),信号回调中表示信号(int)
     * @param $type     int 类型
     */
    public function delOne($resource, $type);

    /**
     * 清除所有的计时器事件
     */
    public function delAllTimer();

    /**
     * 循环事件
     */
    public function loop();
}