<?php
/**
 * User: yongli
 * Date: 17/9/7
 * Time: 00:35
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace App\Core;

/**
 * 日志处理类
 *
 * Class Log
 *
 * @package App\Core
 */
class Log
{
    /**
     * 日志文件对象
     *
     * @var null
     */
    private static $fileResource = null;

    /**
     * 初始化日志文件对象
     */
    private static function getInstance()
    {
        if (is_null(self::$fileResource)) {
            self::$fileResource = fopen(MEEPO_PS_LOG_PATH, 'a');
        }
    }

    /**
     * 写日志
     *
     * @param        $msg
     * @param string $type
     */
    public static function write($msg, $type = 'INFO')
    {
        self::getInstance();
        $type = strtoupper($type);
        if (!in_array($type, array('INFO', 'ERROR', 'FATAL', 'WARNING', "TEST"))) {
            exit('Log type no match');
        }
        $msg = '[' . $type . '][' . date('Y-m-d H:i:s') . '][' . getmypid() . ']' . $msg . "\n";
        fwrite(self::$fileResource, $msg);
        if (MEEPO_PS_DEBUG) {
            echo $msg;
        }
        if ($type === 'FATAL') {
            exit;
        }
    }
}