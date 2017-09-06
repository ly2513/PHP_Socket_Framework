<?php
/**
 * User: yongli
 * Date: 17/9/7
 * Time: 00:33
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace App\Core;

/**
 * 常用函数库
 *
 * Class Functions
 *
 * @package App\Core
 */
class Functions
{
    /**
     * 数组的KEY变更为项中的ID
     *
     * @param        $arr
     * @param string $key
     *
     * @return array
     */
    public static function  arrayKey($arr, $key = 'id')
    {
        $data = array();
        foreach ($arr as $a) {
            $data[$a[$key]] = $a;
        }
        return $data;
    }

    /**
     * 设置程序名称
     *
     * @param $title
     */
    public static function setProcessTitle($title)
    {
        if (function_exists('cli_set_process_title')) {
            @cli_set_process_title($title);
        } elseif (extension_loaded('proctitle') && function_exists('setproctitle')) {
            @setproctitle($title);
        }
    }

    /**
     * 获得当前用户
     *
     * @return mixed
     */
    public static function getCurrentUser()
    {
        $userInfo = posix_getpwuid(posix_getuid());
        return $userInfo['name'];
    }
}