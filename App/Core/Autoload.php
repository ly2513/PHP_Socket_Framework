<?php
/**
 * User: yongli
 * Date: 17/9/6
 * Time: 23:25
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace App\Core;

class Autoload
{

    const NAMESPACE_PREFIX = 'App\\';

    /**
     * 注册自动载入的类和函数
     */
    public static function register()
    {
        spl_autoload_register([new self, 'autoload']);
    }

    /**
     * 根据命名空间载入所在文件
     *
     * @param $className
     */
    public static function autoload($className)
    {
        // 获得命名空间前缀的长度
        $namespacePrefixStrLen = strlen(self::NAMESPACE_PREFIX);
        if (strncmp(self::NAMESPACE_PREFIX, $className, $namespacePrefixStrLen) === 0) {
            $filePath = str_replace('\\', DIRECTORY_SEPARATOR, substr($className, $namespacePrefixStrLen));
            $realPath = realpath(ROOT_PATH . (empty($filePath) ? '' : DIRECTORY_SEPARATOR) . $filePath . '.php');
            if (file_exists($realPath)) {
                require_once $realPath;
            } else {
                die('File Not Exists. filePath: ' . $filePath . ', realPath: ' . $realPath . ' ,class:' . $className . "\n");
            }
        }
    }
}

Autoload::register();