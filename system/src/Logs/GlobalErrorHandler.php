<?php

declare(strict_types=1);

namespace Johncms\Logs;

use ErrorException;
use Johncms\Users\User;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

final class GlobalErrorHandler
{
    private bool $handling = false;

    /**
     * @param LoggerInterface $logger   PSR-3 logger
     * @param array<int> $ignoredErrors Errors to be ignored
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ContainerInterface $container,
        private readonly array $ignoredErrors = []
    ) {
    }

    /**
     * Register error and exception handlers
     */
    public function registerHandlers(): void
    {
        error_reporting(-1);
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
        register_shutdown_function([$this, 'handleShutdown']);
    }

    private function isDeprecation(int $level): bool
    {
        return in_array($level, [E_DEPRECATED, E_USER_DEPRECATED], true);
    }

    private function isWarning(int $level): bool
    {
        return in_array($level, [E_WARNING, E_USER_WARNING, E_NOTICE, E_USER_NOTICE], true);
    }

    /**
     * Handle regular errors
     *
     * @throws ErrorException
     */
    public function handleError(int $level, string $message, string $file = '', int $line = 0): bool
    {
        if (in_array($level, $this->ignoredErrors, true)) {
            return true;
        }

        // Respect the error suppression operator (@) and error_reporting()
        if (! (error_reporting() & $level)) {
            return true;
        }

        $logContext = [
            'level'  => $level,
            'file'   => $file,
            'line'   => $line,
            'url'    => $_SERVER['REQUEST_URI'] ?? null,
            'method' => $_SERVER['REQUEST_METHOD'] ?? null,
            'ip'     => $_SERVER['REMOTE_ADDR'] ?? null,
        ];

        if ($this->isDeprecation($level) || $this->isWarning($level)) {
            $this->logger->warning($message, $logContext);
            return true;
        }

        throw new ErrorException($message, 0, $level, $file, $line);
    }

    /**
     * Handle exceptions
     *
     * @throws Throwable
     */
    public function handleException(Throwable $throwable): void
    {
        $this->handleAppException($throwable);
    }

    /**
     * @throws Throwable
     */
    public function handleShutdown(): void
    {
        $lastError = error_get_last();

        if (! is_array($lastError)) {
            return;
        }

        $type = $lastError['type'];

        if (in_array($type, $this->ignoredErrors, true)) {
            return;
        }

        if (! (error_reporting() & $type)) {
            return;
        }

        if ($this->isDeprecation($type) || $this->isWarning($type)) {
            $this->logger->warning(
                $lastError['message'],
                [
                    'level'  => $type,
                    'file'   => $lastError['file'],
                    'line'   => $lastError['line'],
                    'url'    => $_SERVER['REQUEST_URI'] ?? null,
                    'method' => $_SERVER['REQUEST_METHOD'] ?? null,
                    'ip'     => $_SERVER['REMOTE_ADDR'] ?? null,
                ]
            );

            return;
        }

        $this->handleAppException(
            new ErrorException(
                $lastError['message'],
                0,
                $lastError['type'],
                $lastError['file'],
                $lastError['line']
            )
        );
    }

    /**
     * @throws Throwable
     */
    public function handleAppException(Throwable $exception): void
    {
        if ($this->handling) {
            exit(1);
        }

        $this->handling = true;

        $context = $this->getExceptionContext($exception);
        $this->logger->error($exception->getMessage(), $context);
        $this->renderException($exception);
    }

    private function getExceptionContext(Throwable $exception): array
    {
        $context = [
            'file'      => $exception->getFile(),
            'line'      => $exception->getLine(),
            'url'       => $_SERVER['REQUEST_URI'] ?? null,
            'method'    => $_SERVER['REQUEST_METHOD'] ?? null,
            'ip'        => $_SERVER['REMOTE_ADDR'] ?? null,
            'exception' => $exception,
        ];

        if (method_exists($exception, 'context')) {
            $context = array_merge($context, $exception->context());
        }

        return $context;
    }

    private function renderException(Throwable $exception): void
    {
        http_response_code(500);

        if ($this->canShowDebug()) {
            if (php_sapi_name() === 'cli') {
                $message = $exception;
            } else {
                $message = "<pre>$exception</pre>";
            }
        } else {
            $message = "Internal Server Error";
        }

        echo $message;
        exit(1);
    }

    private function canShowDebug(): bool
    {
        if (php_sapi_name() === 'cli' || DEBUG_FOR_ALL) {
            return true;
        }

        if (! DEBUG) {
            return false;
        }

        try {
            return $this->container->get(User::class)?->rights > 0;
        } catch (Throwable) {
            return false;
        }
    }
}
