<?php

namespace Core\Logger;

interface LoggerInterface
{

    public function fatal($message, array $context = []);

    public function error($message, array $context = []);

    public function warn($message, array $context = []);

    public function info($message, array $context = []);

    public function debug($message, array $context = []);
}
