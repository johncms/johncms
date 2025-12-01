<?php

declare(strict_types=1);

namespace Johncms\Logs;

use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Psr\Container\ContainerInterface;
use RuntimeException;

class LoggerFactory
{
    public function __invoke(ContainerInterface $container): Logger
    {
        $loggingConfig = $container->get('config')['logging'];
        $defaultHandler = $loggingConfig['default'] ?? 'file';
        $handlersConfig = $loggingConfig['handlers'] ?? [];

        if (! isset($handlersConfig[$defaultHandler])) {
            throw new RuntimeException(
                sprintf('Logger handler "%s" is not configured.', $defaultHandler)
            );
        }

        $handlerConfig = $handlersConfig[$defaultHandler];

        $logger = new Logger('johncms');

        switch ($defaultHandler) {
            case 'file':
                $path = $handlerConfig['path'] ?? LOG_PATH . 'johncms.log';
                $days = (int) ($handlerConfig['days'] ?? 10);

                if ($days > 0) {
                    $logger->pushHandler(new RotatingFileHandler($path, $days, Level::Debug));
                } else {
                    $logger->pushHandler(new StreamHandler($path, Level::Debug));
                }
                break;

            default:
                throw new RuntimeException(
                    sprintf('Logger handler "%s" is not supported.', $defaultHandler)
                );
        }

        return $logger;
    }
}
