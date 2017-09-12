<?php
/**
 * User: yongli
 * Date: 17/9/12
 * Time: 22:43
 * Email: yong.li@szypwl.com
 * Copyright: 深圳优品未来科技有限公司
 */
namespace App\Core\Trident;

use App\Core\MeepoPS;

/**
 * 业务逻辑层
 * 接收Transfer发来的请求, 进行业务逻辑处理, 返回给Transfer, 最后Transfer返回给用户。
 *
 * Class Business
 *
 * @package App\Core\Trident
 */
class Business extends MeepoPS{

    /**
     * confluence的IP
     *
     * @var
     */
    public $confluenceIp;

    /**
     * confluence的端口
     *
     * @var
     */
    public $confluencePort;

    /**
     * Business constructor.
     */
    public function __construct()
    {
        $this->callbackStartInstance = array($this, 'callbackBusinessStartInstance');
        parent::__construct();
    }

    /**
     * 进程启动时, 链接到Confluence
     * 作为客户端, 连接到中心机(Confluence层), 获取Transfer列表
     */
    public function callbackBusinessStartInstance(){
        // 作为客户端, 连接到中心机(Confluence层), 获取Transfer列表
        $businessAndConfluenceService = new BusinessAndConfluenceService();
        $businessAndConfluenceService->confluenceIp = $this->confluenceIp;
        $businessAndConfluenceService->confluencePort = $this->confluencePort;
        $businessAndConfluenceService->connectConfluence();
    }
}