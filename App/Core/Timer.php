<?php
/**
 * User: yongli
 * Date: 17/9/7
 * Time: 00:42
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace App\Core;

use App\Core\Event\EventInterface;

/**
 * 定时器
 *
 * Class Timer
 *
 * @package App\Core
 */
class Timer
{
    // 事件机制.为空时则使用alarm信号来完成定时器机制.不为空时依赖Select/Libevent等
    private static $_event = null;
    // 任务列表
    private static $_taskList = [];
    // 任务ID
    private static $_id = 1;

    /**
     * 初始化定时器
     *
     * @param object $event
     */
    public static function init($event = null)
    {
        // 如果没有事件,则安装一个alarm信号处理器
        is_object($event) ? (self::$_event = $event) : (pcntl_signal(SIGALRM, ['\App\Core\Timer', 'signalCallback'],
            false));
    }

    /**
     * 添加任务
     *
     * @param $callback       string|array 回调函数
     * @param $args           array 参数
     * @param $intervalSecond int 每次执行的间隔时间,单位秒,必须大于0
     * @param $isAlways       bool|true 是否一直执行,默认为true. 一次性任务请传入false
     *
     * @return int|false
     */
    public static function add($callback, array $args, $intervalSecond, $isAlways = true)
    {
        if ($intervalSecond <= 0 || !is_callable($callback)) {
            return false;
        }
        if (!is_null(self::$_event)) {
            return self::$_event->add($callback, $args, $intervalSecond,
                $isAlways ? EventInterface::EVENT_TYPE_TIMER : EventInterface::EVENT_TYPE_TIMER_ONCE);
        } else {
            pcntl_alarm(1);
            $startTime                 = time() + $intervalSecond;
            $timerId                   = self::$_id++;
            self::$_taskList[$timerId] = [$callback, $args, $startTime, $intervalSecond, $isAlways];

            return $timerId;
        }
    }

    /**
     * 删除一个定时器
     *
     * @param $timerId
     */
    public static function delOne($timerId)
    {
        if (!is_null(self::$_event)) {
            self::$_event->delOne($timerId, EventInterface::EVENT_TYPE_TIMER);
        } else {
            unset(self::$_taskList[$timerId]);
        }
    }

    /**
     * 删除所有的定时器任务
     */
    public static function delAll()
    {
        self::$_taskList = [];
        pcntl_alarm(0);
        if (!is_null(self::$_event)) {
            self::$_event->delAllTimer();
        }
    }

    /**
     * 信号处理函数
     */
    public static function signalCallback()
    {
        //没有事件机制,并且队列不为空,则使用alarm信号
        if (is_null(self::$_event) && !empty(self::$_taskList)) {
            //创建一个计时器，每秒向进程发送一个alarm信号。
            pcntl_alarm(1);
            self::_execute();
        }
    }

    /**
     * 执行任务
     */
    private static function _execute()
    {
        $nowTime = time();
        foreach (self::$_taskList as $timerId => $task) {
            //当前时间小于启动时间,则不启动该时间段任务
            if ($nowTime < $task[2]) {
                continue;
            }
            //如果是持续性定时器任务,则添加到下次执行的队伍中
            if ($task[4]) {
                self::add($task[0], $task[1], $task[3], $task[4]);
            }
            //执行回调函数
            try {
                call_user_func_array($task[0], $task[1]);
            } catch (\Exception $e) {
                Log::write('YP_Socket: execution callback function timer execute-' . $task[0] . ' throw exception' . json_encode($e),
                    'ERROR');
            }
            //删除本次已经执行的任务
            unset(self::$_taskList[$timerId]);
        }
    }
}