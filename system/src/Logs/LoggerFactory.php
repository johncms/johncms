<?php

declare(strict_types=1);

namespace Johncms\Logs;

use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use RuntimeException;

final readonly class LoggerFactory
{
    private const FILENAME_FORMAT = '{filename}-{date}';
    private const DATE_FORMAT = 'Y-m-d';

    public function __invoke(): Logger
    {
        $loggingConfig = config('logging');
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
                    $logger->pushHandler(
                        new RotatingFileHandler(
                            filename: $path,
                            maxFiles: $days,
                            level: Level::Debug,
                            dateFormat: self::DATE_FORMAT,
                            filenameFormat: self::FILENAME_FORMAT,
                        )
                    );
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

    /**
     * The file the logger writes to right now.
     *
     * PHP logs by itself everything that happens before the error handlers are registered, and
     * that has to land in the same file instead of a second one next to it.
     */
    public static function currentFile(): string
    {
        $loggingConfig = config('logging');
        $defaultHandler = $loggingConfig['default'] ?? 'file';
        $handlerConfig = $loggingConfig['handlers'][$defaultHandler] ?? [];

        $path = $handlerConfig['path'] ?? LOG_PATH . 'johncms.log';
        if ((int) ($handlerConfig['days'] ?? 10) <= 0) {
            return $path;
        }

        $pathInfo = pathinfo($path);
        $name = str_replace(
            ['{filename}', '{date}'],
            [$pathInfo['filename'], date(self::DATE_FORMAT)],
            self::FILENAME_FORMAT
        );

        return $pathInfo['dirname'] . DIRECTORY_SEPARATOR . $name
            . (isset($pathInfo['extension']) ? '.' . $pathInfo['extension'] : '');
    }
}
