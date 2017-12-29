<?php
namespace Core;

use \App;
use Core\Logger\LoggerInterface;

/**
 * service基类
 *
 * service的设计说明：
 * - service接口主要用于封装业务逻辑、数据库操作、缓存处理，然后返回结果。
 * - service应该是无状态，可复用的，即不应该在service接口里获取当前登录的用户ID，进行session、cookie处理，而是应该通过传参的方式调用。
 * - 如果在service中发生错误，应该抛出 ServiceException 类型的异常，而不能直接终止程序。
 *
 * @author lisijie <lsj86@qq.com>
 * @package Core
 */
abstract class AbstractService extends Component
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var array
     */
    protected static $instances = [];

    /**
     * 不允许直接实例化
     */
    private final function __construct()
    {
        $this->logger = App::logger($this->className(true));
        $this->init();
    }

    /**
     * 获取实例
     * @return static
     */
    public final static function getInstance()
    {
        $cls = get_called_class();
        if (!array_key_exists($cls, static::$instances)) {
            static::$instances[$cls] = self::create();
        }
        return static::$instances[$cls];
    }

    /**
     * 创建实例
     * @return static
     */
    public final static function create()
    {
        return new static();
    }

    /**
     * 初始化方法
     */
    protected function init()
    {
        // 初始化方法
    }
}
