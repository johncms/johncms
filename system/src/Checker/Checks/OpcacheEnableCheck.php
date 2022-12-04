<?php

declare(strict_types=1);

namespace Johncms\Checker\Checks;

use Johncms\Checker\CheckInterface;
use Johncms\Checker\SystemChecker;

class OpcacheEnableCheck implements CheckInterface
{
    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'opcache.enable=1';
    }

    /**
     * @inheritDoc
     */
    public function getValue(): string
    {
        return $this->isError() ? d__('system', 'No') : d__('system', 'Yes');
    }

    /**
     * @inheritDoc
     */
    public function isError(): bool
    {
        return ini_get('opcache.enable') !== '1';
    }

    /**
     * @inheritDoc
     */
    public function getErrorLevel(): int
    {
        return SystemChecker::WARNING;
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return d__('system', 'It is recommended to enable the php opcache extension to improve performance.');
    }
}
