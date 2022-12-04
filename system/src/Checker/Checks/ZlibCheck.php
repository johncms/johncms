<?php

declare(strict_types=1);

namespace Johncms\Checker\Checks;

use Johncms\Checker\CheckInterface;
use Johncms\Checker\SystemChecker;

class ZlibCheck implements CheckInterface
{
    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return d__('system', 'zlib extension');
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
        return ! extension_loaded('zlib');
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
        return d__('system', 'PHP extension zlib must be installed');
    }
}
