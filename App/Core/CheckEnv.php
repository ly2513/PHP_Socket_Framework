<?php
/**
 * User: yongli
 * Date: 17/9/6
 * Time: 23:13
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace App\Core;

$fatalErrorList   = [];
$warningErrorList = [];

// 要求PHP环境必须大于PHP5.3
if (!substr(PHP_VERSION, 0, 3) >= '5.3') {
    $fatalErrorList[] = 'Fatal error: Framework requires PHP version must be greater than 5.3(contain 5.3). Because Framework used php-namespace';
}

// 不支持在Windows下运行
if (strpos(strtolower(PHP_OS), 'win') === 0) {
    $fatalErrorList[] = 'Fatal error: Framework not support Windows. Because the required extension is supported only by Linux, such as php-pcntl, php-posix';
}

// 必须运行在命令行下
if (php_sapi_name() != 'cli') {
    $fatalErrorList[] = 'Fatal error: Framework must run in command line!';
}

// 是否已经安装PHP-pcntl 扩展
if (!extension_loaded('pcntl')) {
    $fatalErrorList[] = 'Fatal error: Framework must require php-pcntl extension. Because the signal monitor, multi process needs php-pcntl\nPHP manual: http://php.net/manual/zh/intro.pcntl.php';
}

// 是否已经安装PHP-posix 扩展
if (!extension_loaded('posix')) {
    $fatalErrorList[] = 'Fatal error: Framework must require php-posix extension. Because send a signal to a process, get the real user ID of the current process needs php-posix' . "\n" . 'PHP manual: http://php.net/manual/zh/intro.posix.php';
}

// 启动参数是否正确
global $argv;

if (!isset($argv[1]) || !in_array($argv[1], ['start', 'stop', 'restart', 'status', 'kill'])) {
    $fatalErrorList[] = 'Fatal error: Framework needs to receive the execution of the operation.' . "\n" . 'Usage: php index.php start|stop|restart|status|kill' . "\n\"";
}

// 日志路径是否已经配置
if (!defined('LOG_PATH')) {
    $fatalErrorList[] = 'Fatal error: Log file path is not defined. Please define LOG_PATH in Config.php';
} else {
    // 日志目录是否存在
    if (!file_exists(dirname(LOG_PATH))) {
        if (@!mkdir(dirname(LOG_PATH), 0777, true)) {
            $fatalErrorList[] = 'Fatal error: Log file directory creation failed: ' . dirname(LOG_PATH);
        }
    }
    // 日志目录是否可写
    if (!is_writable(dirname(LOG_PATH))) {
        $fatalErrorList[] = 'Fatal error: Log file path not to be written: ' . dirname(LOG_PATH);
    }
}

// 主进程Pid文件路径是否已经配置
if (!defined('MASTER_PID_PATH')) {
    $fatalErrorList[] = 'Fatal error: master pid file path is not defined. Please define MASTER_PID_PATH in Config.php';
} else {
    // 主进程Pid文件目录是否存在
    if (!file_exists(dirname(MASTER_PID_PATH))) {
        if (@!mkdir(dirname(MASTER_PID_PATH), 0777, true)) {
            $fatalErrorList[] = 'Fatal error: master pid file directory creation failed: ' . dirname(MASTER_PID_PATH);
        }
    }
    // 主进程Pid文件目录是否可写
    if (!is_writable(dirname(MASTER_PID_PATH))) {
        $fatalErrorList[] = 'Fatal error: master pid file path not to be written: ' . dirname(MASTER_PID_PATH);
    }
}

//标准输出路径是否已经配置
if (!defined('STDOUT_PATH')) {
    $warningErrorList[] = 'Warning error: standard output file path is not defined. Please define STDOUT_PATH in Config.php';
} else if (STDOUT_PATH !== '/dev/null') {
    // 标准输出目录是否存在
    if (!file_exists(dirname(STDOUT_PATH))) {
        if (@!mkdir(dirname(STDOUT_PATH), 0777, true)) {
            $warningErrorList[] = 'Warning error: standard output file directory creation failed: ' . dirname(STDOUT_PATH);
        }
    }
    // 标准输出目录是否可写
    if (!is_writable(dirname(STDOUT_PATH))) {
        $warningErrorList[] = 'Warning error: standard output file path not to be written: ' . dirname(STDOUT_PATH);
    }
}

// 统计信息存储文件路径是否已经配置
if (!defined('STATISTICS_PATH')) {
    $warningErrorList[] = 'Warning error: statistics file path is not defined. Please define STATISTICS_PATH in Config.php';
} else {
    // 统计信息存储文件目录是否存在
    if (!file_exists(dirname(STATISTICS_PATH))) {
        if (@!mkdir(dirname(STATISTICS_PATH), 0777, true)) {
            $warningErrorList[] = 'Warning error: statistics file directory creation failed: ' . dirname(STATISTICS_PATH);
        }
    }
    // 统计信息存储文件目录是否可写
    if (!is_writable(dirname(STATISTICS_PATH))) {
        $warningErrorList[] = 'Warning error: statistics file path not to be written: ' . dirname(STATISTICS_PATH);
    }
}

if ($fatalErrorList) {
    $fatalErrorList = implode("\n\n", $fatalErrorList);
    exit($fatalErrorList);
}

if ($warningErrorList) {
    $warningErrorList = implode("\n\n", $warningErrorList);
    echo $warningErrorList . "\n\n";
}

unset($fatalErrorList);
unset($warningErrorList);