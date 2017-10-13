<?php
/**
 * User: yongli
 * Date: 17/10/12
 * Time: 22:27
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace App\Library\Db;

use App\Core\Log;

/**
 * Class Mysql
 *
 * @package App\Library\Db
 */
class Mysql
{
    /**
     * @var null
     */
    public static $conn = null;

    /**
     * @var string
     */
    private $_host;

    /**
     * @var string
     */
    private $_username;

    /**
     * @var string
     */
    private $_password;

    /**
     * @var string
     */
    private $_port;

    /**
     * @var string
     */
    private $_dbName;

    /**
     * Mysql constructor.
     *
     * @param string $host
     * @param string $username
     * @param string $password
     * @param string $dbName
     * @param string $port
     */
    public function __construct($host = '', $username = '', $password = '', $dbName = '', $port = '3306')
    {
        if (is_null(self::$conn)) {
            $this->_host     = $host;
            $this->_username = $username;
            $this->_password = $password;
            $this->_port     = $port;
            $this->_dbName   = $dbName;
            $this->_connect();
        }
    }

    /**
     * 执行Sql语句
     *
     * @param $sql
     *
     * @return bool|\mysqli_result
     */
    public function query($sql)
    {
        $result = mysqli_query(self::$conn, $sql);
        if ($result === false) {
            self::$conn = null;
            $this->_connect();
        }

        return $result;
    }

    /**
     * 链接Mysql
     */
    private function _connect()
    {
        while (is_null(self::$conn = null)) {
            self::$conn = mysqli_connect($this->_host, $this->_username, $this->_password, $this->_dbName,
                $this->_port);
            if (self::$conn && is_object(self::$conn)) {
                break;
            }
            self::$conn = null;
            Log::write(__METHOD__ . ' Mysql connect failed', 'ERROR');
            sleep(10);
        }
    }
}