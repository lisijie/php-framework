<?php

namespace Core\View;

class ViewFactory
{
    /**
     * 工厂方法
     *
     * @param string $type 类型: Native|Smarty
     * @param array $options
     * @return \Core\View\ViewInterface
     * @throws \Core\View\ViewException
     */
    public static function create($type, $options = array())
    {
        $className = '\\Core\\View\\Adapter\\' . ucfirst($type);
        if (!class_exists($className)) {
            throw new ViewException('不支持该视图类型: ' . $type);
        }
        return new $className($options);
    }
}
