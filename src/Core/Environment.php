<?php
namespace Core;

use Core\Exception\CoreException;

/**
 * 运行环境控制
 *
 * 项目从开发到发布一般都需要经过以下步骤：
 * 开发人员在本地进行功能开发 -> 完成开发提交给测试人员测试 -> 发布到预发布环境进行功能检查 -> 上线
 *
 * 对应的运行环境为：
 * - development 开发环境
 * - testing 测试环境
 * - pre_release 预发布环境
 * - production 生产环境
 *
 * 为了让运行环境的配置不入侵到代码中，这里支持两种形式的设置：
 * 1. 通过配置服务器环境变量来指定，代码中通过 $_SERVER[var] 获取。适用于运行在http模式。
 *    在nginx中可以使用 `fastcgi_param ENVIRONMENT development;` 指令来配置一个名为 ENVIRONMENT，值
 *    为 development 的环境变量。apache服务器可以在VirtualHost配置中使用 `SetEnv ENVIRONMENT development` 进行设置。
 * 2. 将当前的环境名称写入到一个外部文件中，然后通过读取这个外部文件内容来获取当前的运行环境。这个适用于运行在命令行模式。
 *
 * @author lisijie <lsj86@qq.com>
 * @package Core
 */
class Environment
{
    // 开发环境
    const DEVELOPMENT = 'development';

    // 测试环境
    const TESTING = 'testing';

    // 预发布环境
    const PRE_RELEASE = 'pre_release';

    // 生产环境
    const PRODUCTION = 'production';

    // 环境变量名称
    private static $envVar = 'ENVIRONMENT';

    // 当前环境
    private static $environment;

    // 环境文件名
    private static $envFile = '';

    // 自定义环境
    private static $customEnvs = [];

    /**
     * 设置环境配置文件
     * @param $filename
     */
    public static function setEnvFile($filename)
    {
        self::$envFile = $filename;
    }

    /**
     * 设置服务器环境变量名
     * @param $varName
     */
    public static function setEnvVar($varName)
    {
        self::$envVar = $varName;
    }

    /**
     * 添加自定义环境名称
     * @param $envName
     */
    public static function addEnvironment($envName)
    {
        self::$customEnvs[] = $envName;
    }

    /**
     * 检查当前是否是某个环境
     * @param $env
     * @return bool
     */
    public static function isEnvironment($env)
    {
        return self::getEnvironment() === $env;
    }

    /**
     * 检查是否开发环境
     * @return bool
     */
    public static function isDevelopment()
    {
        return self::getEnvironment() == self::DEVELOPMENT;
    }

    /**
     * 检查是否测试环境
     * @return bool
     */
    public static function isTesting()
    {
        return self::getEnvironment() == self::TESTING;
    }

    /**
     * 检查是否预发布环境
     * @return bool
     */
    public static function isPreRelease()
    {
        return self::getEnvironment() == self::PRE_RELEASE;
    }

    /**
     * 检查是否生产环境
     * @return bool
     */
    public static function isProduction()
    {
        return self::getEnvironment() == self::PRODUCTION;
    }

    /**
     * 设置当前环境
     * @param $env
     */
    public static function setEnvironment($env)
    {
        if (self::isValid($env)) {
            self::$environment = $env;
        } else {
            exit('invalid env: ' . $env);
        }
    }

    /**
     * 返回当前的环境名称
     *
     * 首先检查是否指定了环境配置文件，如果存在则优先获取配置文件内容来设置当前运行环境。
     * 否则将检查$_SERVER环境变量，如果存在则使用该值进行设置。
     * 如果都没设置，则设为默认的production环境。
     * @return string
     */
    public static function getEnvironment()
    {
        if (!self::$environment) {
            // 指定环境变量文件
            if (!empty(self::$envFile) && is_file(self::$envFile)
                && self::isValid($env = file_get_contents(self::$envFile))
            ) {
                self::$environment = $env;
            }
            // 检查$_SERVER环境变量
            if (!self::$environment && isset($_SERVER[self::$envVar])
                && self::isValid($_SERVER[self::$envVar])
            ) {
                self::$environment = $_SERVER[self::$envVar];
            } else {
                self::$environment = self::getDefaultEnvironment();
            }
        }
        return self::$environment;
    }

    /**
     * 返回默认环境名称
     * @return string
     */
    private static function getDefaultEnvironment()
    {
        return self::PRODUCTION;
    }

    /**
     * 验证是否有效的环境名称
     * @param $env
     * @return bool
     */
    private static function isValid($env)
    {
        $ref = new \ReflectionClass(get_called_class());
        $environments = array_merge(array_values($ref->getConstants()), self::$customEnvs);
        return in_array($env, $environments);
    }
}