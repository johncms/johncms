<?php

declare(strict_types=1);

namespace Johncms\Log;

use Monolog\Handler\HandlerInterface;
use Monolog\Handler\RotatingFileHandler;
use Psr\Log\LoggerInterface;

/**
 * @mixin \Monolog\Logger
 */
class Logger
{
    public function __invoke(): LoggerInterface
    {
        $logger = new \Monolog\Logger('default');
        $logger->pushHandler($this->getHandler());
        return $logger;
    }

    protected function getHandler(): HandlerInterface
    {
        $defaultLogger = config('logging.default', 'file');
        $handlers = config('logging.handlers');
        switch ($defaultLogger) {
            case 'file':
            default:
                return new RotatingFileHandler($handlers['file']['path'], $handlers['file']['days']);
        }
    }
}
