<?php

namespace Core\Exception;

use Exception;
use Core\Http\Response;
use Core\Logger\LoggerInterface;
use Core\Exception\HttpException;

/**
 * 异常处理器
 *
 * @package Core\Exception
 */
class Handler
{
    /**
     * 当前类的实例
     *
     * @var static
     */
    protected static $instance;

    /**
     * 是否debug
     *
     * @var bool
     */
    protected $debug;

    /**
     * 日志记录器
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * 不记录日志的异常列表
     *
     * @var array
     */
    protected $dontReport = array(
        'Core\Exception\HttpException',
    );

    private function __construct(LoggerInterface $logger, $debug = false)
    {
        $this->logger = $logger;
        $this->debug = $debug;
    }

    public static function factory(LoggerInterface $logger, $debug = false)
    {
        if (!static::$instance) {
            static::$instance = new static($logger, $debug);
        }
        return static::$instance;
    }

    /**
     * 注册异常处理器
     */
    public function register()
    {
        // 注册错误处理函数，将所有错误转为异常
        set_error_handler(function ($code, $str, $file, $line) {
            throw new \ErrorException($str, $code, 0, $file, $line);
        });
        // 注册异常处理函数
        set_exception_handler(array($this, 'handle'));
        // 注册shutdown函数, 记录运行时无法捕获的错误
        $this->registerShutdown();
    }

    /**
     * shutdown函数, 记录运行时无法捕获的错误
     */
    protected function registerShutdown()
    {
        register_shutdown_function(function() {
            $error = error_get_last();
            if ($error) {
                $errTypes = array(E_ERROR => 'E_ERROR', E_PARSE => 'E_PARSE', E_USER_ERROR => 'E_USER_ERROR');
                if (isset($errTypes[$error['type']])) {
                    $info = $errTypes[$error['type']] . ": {$error['message']} in {$error['file']} on line {$error['line']}";
                    $this->logger->error($info);
                }
            }
        });
    }

    /**
     * 异常信息上报
     *
     * @param Exception $e
     * @return bool
     */
    protected function report(Exception $e)
    {
        foreach ($this->dontReport as $className) {
            if ($e instanceof $className) {
                return false;
            }
        }

        $this->logger->error((string)$e);
        return true;
    }

    /**
     * 输出异常信息
     *
     * @param Exception $e
     */
    protected function render(Exception $e)
    {
        if ($this->isHttpException($e)) {
            return $this->renderHttpException($e);
        }

        if ($this->debug) {
            return $this->renderDebugInfo($e);
        }

        exit('系统发生异常: '.get_class($e).'('.$e->getCode().')');
    }

    /**
     * 输出debug信息
     *
     * @param Exception $e
     */
    protected function renderDebugInfo(Exception $e)
    {
        $errType = get_class($e);

        if (PHP_SAPI == 'cli') {
            echo "----------------------------------------------------------------------------------------------------\n";
            echo "exception: {$errType}\n";
            echo "error: {$e->getMessage()} (#{$e->getCode()})\n";
            echo "file: {$e->getFile()} ({$e->getLine()})\n";
            echo "----------------------------------------------------------------------------------------------------\n";
            echo $e->getTraceAsString();
            echo "\n----------------------------------------------------------------------------------------------------\n";
        } else {
            $errMsg = 'error: ' . $e->getMessage() . '<br />errno: ' . $e->getCode();
            $errMsg .= '<br />file: ' . $e->getFile() . '<br />line: ' . $e->getLine();

            echo '<div style="margin:20px;">';
            echo '<p style="color:red;font-family:Verdana;line-height:150%;">[' . $errType . ']</p>';
            echo '<p style="font-size:11px;font-family:Verdana; background:#ffffdd; border:1px solid #f0f0f0; padding:5px">';
            echo $errMsg;
            echo '<br />time: ' . date('Y-m-d H:i:s');
            echo '</p>';

            echo '<p style="color:red;font-family:Verdana;line-height:150%;">[PHP Debug]</p>';
            echo '<pre style="font-size:11px;font-family:Verdana; background:#e7f7ff; border:1px solid #f0f0f0; padding:5px; line-height: 150%">';
            echo $e->getTraceAsString();
            echo '</pre>';
            echo '</div>';
        }        
    }

    /**
     * 输出HTTP异常信息
     *
     * @param HttpException $e
     */
    protected function renderHttpException(HttpException $e)
    {
        $message = $e->getMessage();
        if (empty($message)) {
            $message = Response::getStatusText($e->getCode());
        }
        $response = new Response();
        $response->setStatus($e->getCode());
        $response->setContent("<h1>{$message}</h1>");
        $response->send();
    }

    /**
     * 是否http异常
     *
     * @param Exception $e
     * @return bool
     */
    protected function isHttpException(Exception $e)
    {
        return ($e instanceof HttpException);
    }

    /**
     * 处理异常
     *
     * @param Exception $e
     */
    public function handle(Exception $e)
    {
        $this->report($e);
        $this->render($e);
        exit;
    }
}
