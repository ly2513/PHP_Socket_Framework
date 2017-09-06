<?php
/**
 * User: yongli
 * Date: 17/9/6
 * Time: 23:33
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace MeepoPS\Core;

// 框架当前状态 - 启动中
define('STATUS_STARTING', 1);

// 框架当前状态 - 运行中
define('STATUS_RUNNING', 2);

// 框架当前状态 - 关闭中
define('STATUS_CLOSING', 4);

// 框架当前状态 - 停止
define('STATUS_SHUTDOWN', 8);

// 框架的Backlog.Backlog来自TCP协议.backlog是一个连接队列,队列总和=未完成三次握手队列+已经完成三次握手队列.Accept时从已经完成三次握手队列的取出一个链接.
define('BACKLOG', 2048);

// UDP协议下所允许的最大包
define('MAX_UDP_PACKET_SIZE', 65535);