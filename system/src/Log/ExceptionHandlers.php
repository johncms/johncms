<?php

declare(strict_types=1);

namespace Johncms\Log;

use ErrorException;
use Johncms\Users\User;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

class ExceptionHandlers
{
    protected LoggerInterface $logger;

    public function __construct(
        ContainerInterface $container
    ) {
        $this->logger = $container->get(LoggerInterface::class);
    }

    public function __invoke(): static
    {
        return $this;
    }

    public function registerHandlers(): void
    {
        error_reporting(-1);
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
        register_shutdown_function([$this, 'handleShutdown']);
    }

    protected function isDeprecation(int $level): bool
    {
        return in_array($level, [E_DEPRECATED, E_USER_DEPRECATED]);
    }

    /**
     * @throws ErrorException
     */
    public function handleError(int $level, string $message, string $file = '', int $line = 0, array $context = []): bool
    {
        if ($this->isDeprecation($level)) {
            $this->logger->warning((string) new ErrorException($message, 0, $level, $file, $line));
            return false;
        }

        if (error_reporting() & $level) {
            throw new ErrorException($message, 0, $level, $file, $line);
        }
        return false;
    }

    /**
     * @throws Throwable
     */
    public function handleException(Throwable $throwable): void
    {
        $this->handleAppException($throwable);
    }

    /**
     * @throws ErrorException
     * @throws Throwable
     */
    public function handleShutdown(): void
    {
        $lastError = error_get_last();
        if (is_array($lastError)) {
            $this->handleAppException(new ErrorException($lastError['message'], 0, $lastError['type'], $lastError['file'], $lastError['line']));
        }
    }

    /**
     * @throws Throwable
     */
    public function handleAppException(Throwable $exception): void
    {
        $exceptionContext = [];
        if (method_exists($exception, 'context')) {
            $exceptionContext = $exception->context();
        }

        $this->logger->error(
            $exception->getMessage(),
            array_merge(
                $exceptionContext,
                ['exception' => $exception]
            )
        );

        // Show an exception message if debug mode is on or show "Internal Server Error" if debug mode is off
        http_response_code(500);
        if (DEBUG_FOR_ALL || (DEBUG && di(User::class)?->isAdmin())) {
            die('<pre>' . $exception . '</pre>');
        } else {
            die('Internal Server Error');
        }
    }
}
