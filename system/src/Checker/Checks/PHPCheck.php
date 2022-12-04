<?php

declare(strict_types=1);

namespace Johncms\Checker\Checks;

use Johncms\Checker\CheckInterface;
use Johncms\Checker\SystemChecker;

class PHPCheck implements CheckInterface
{
    public const MIN_PHP_VERSION = '8.0';
    public const MIN_PHP_VERSION_ID = 80000;

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return d__('system', 'PHP version');
    }

    /**
     * @inheritDoc
     */
    public function getValue(): string
    {
        return PHP_VERSION;
    }

    /**
     * @inheritDoc
     */
    public function isError(): bool
    {
        return (PHP_VERSION_ID < self::MIN_PHP_VERSION_ID);
    }

    /**
     * @inheritDoc
     */
    public function getErrorLevel(): int
    {
        return SystemChecker::CRITICAL;
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return d__('system', 'The PHP version must be at least %s', self::MIN_PHP_VERSION);
    }
}
