<?php

declare(strict_types=1);

namespace Johncms\Logs;

use ErrorException;
use Johncms\Users\User;
use Psr\Log\LoggerInterface;
use Throwable;

final readonly class GlobalErrorHandler
{
    /**
     * @param LoggerInterface $logger   PSR-3 logger
     * @param array<int> $ignoredErrors Errors to be ignored
     */
    public function __construct(
        private LoggerInterface $logger,
        private array $ignoredErrors = []
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

    /**
     * Handle regular errors
     *
     * @throws ErrorException
     */
    public function handleError(
        int $level,
        string $message,
        string $file = '',
        int $line = 0,
        array $context = []
    ): bool {
        if (in_array($level, $this->ignoredErrors, true)) {
            return false;
        }

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
        if (
            is_array($lastError) &&
            (error_reporting() & $lastError['type'])
        ) {
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
    }

    /**
     * @throws Throwable
     */
    public function handleAppException(Throwable $exception): void
    {
        $context = $this->getExceptionContext($exception);
        $this->logger->error($exception->getMessage(), $context);
        $this->renderException($exception);
    }

    protected function getExceptionContext(Throwable $exception): array
    {
        $context = [
            'file'   => $exception->getFile(),
            'line'   => $exception->getLine(),
            'trace'  => $exception->getTraceAsString(),
            'url'    => $_SERVER['REQUEST_URI'] ?? null,
            'method' => $_SERVER['REQUEST_METHOD'] ?? null,
            'ip'     => $_SERVER['REMOTE_ADDR'] ?? null,
        ];

        if (method_exists($exception, 'context')) {
            $context = array_merge($context, $exception->context());
        }

        return ['exception' => $exception] + $context;
    }

    protected function renderException(Throwable $exception): void
    {
        http_response_code(500);

        if (DEBUG_FOR_ALL || (DEBUG && di(User::class)?->rights > 0) || php_sapi_name() === 'cli') {
            $message = "<pre>$exception</pre>";
        } else {
            $message = php_sapi_name() === 'cli' ? "Internal Server Error\n" : "Internal Server Error";
        }

        echo $message;
        exit(1);
    }
}
