<?php
/**
 * User: yongli
 * Date: 17/9/7
 * Time: 00:38
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace App\Core;

/**
 * 主进程, 子进程运行状态和统计
 *
 * Class Statistic
 *
 * @package App\Core
 */
class Statistic
{
    /**
     * 开始收集统计信息, 并且输出
     */
    public static function display()
    {
        // 输出主进程统计信息
        self::_displayStatisticMasterProcess();
        // 输出子进程统计信息
        self::_displayStatisticChildProcess();
    }

    /**
     * 输出主进程
     */
    private static function _displayStatisticMasterProcess()
    {
        $masterStatistic = @file_get_contents(MEEPO_PS_STATISTICS_PATH . '_master');
        $masterStatistic = json_decode($masterStatistic, true);
        echo "-------------------------MeepoPS Statistic-------------------------\n";
        echo 'YP_SOCKET Version: ' . MEEPO_PS_VERSION . ' | PHP: ' . PHP_VERSION . ' | Master Pid: ' . $masterStatistic['master_pid'] . "\n";
        echo 'Start time: ' . $masterStatistic['start_time'] . ' | Event: ' . $masterStatistic['event'] . "\n";
        echo 'Instance count: ' . $masterStatistic['total_instance_count'] . ' | Child Process count: ' . $masterStatistic['total_child_process_count'] . "\n";
        echo "------------------------Process Exit State------------------------\n";
        foreach ($masterStatistic['instance_exit_info'] as $instanceExitInfo) {
            foreach ($instanceExitInfo['status'] as $status => $count) {
                echo $instanceExitInfo['info']['instanceName'] . ' | Status: ' . $status . ' | count: ' . $count . "\n";
            }
        }
        echo empty($masterStatistic['instance_exit_info']) ? "                         No Exit Process                         \n" : '';
    }

    /**
     * 输出子进程
     */
    private static function _displayStatisticChildProcess()
    {
        $childStatisticList = @array_map('file_get_contents', glob(MEEPO_PS_STATISTICS_PATH . '_child_*'));
        echo "---------------------Child Process Statistic----------------------\n";
        foreach ($childStatisticList as $childStatistic) {
            echo "##################################################################\n";
            $childStatistic = json_decode($childStatistic, true);
            echo 'Instance Name: ' . $childStatistic['instance_name'] . ' ( ' . $childStatistic['bind'] . " )\n";
            echo 'Pid: ' . $childStatistic['pid'] . ' | Memory: ' . $childStatistic['memory'] . ' | Exception count: ' . $childStatistic['transport_protocol_statistics']['exception_count'] . "\n";
            echo 'Total Connect: ' . $childStatistic['transport_protocol_statistics']['total_connect_count'] . ' | Current Connect: ' . $childStatistic['transport_protocol_statistics']['current_connect_count'] . "\n";
            echo 'Total Read: ' . $childStatistic['transport_protocol_statistics']['total_read_count'] . ' | Total Read Failed: ' . $childStatistic['transport_protocol_statistics']['total_read_failed_count'] . "\n";
            echo 'Total Read Package: ' . $childStatistic['transport_protocol_statistics']['total_read_package_count'] . ' | Total Read Package Failed: ' . $childStatistic['transport_protocol_statistics']['total_read_package_failed_count'] . "\n";
            echo 'Total Send: ' . $childStatistic['transport_protocol_statistics']['total_send_count'] . ' | Total Send Failed: ' . $childStatistic['transport_protocol_statistics']['total_send_failed_count'] . "\n";
        }
        echo "##################################################################\n";
    }
}