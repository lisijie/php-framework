<?php

namespace Core\Logger;

interface LoggerInterface
{

    public function fatal($message, array $context = array());

    public function error($message, array $context = array());

    public function warn($message, array $context = array());

    public function info($message, array $context = array());

    public function debug($message, array $context = array());

    public function log($level, $message, array $context = array());
}
