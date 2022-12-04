<?php

declare(strict_types=1);

namespace Johncms\Checker\Checks;

use Johncms\Checker\CheckInterface;
use Johncms\Checker\SystemChecker;

class OpcacheCheck implements CheckInterface
{
    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return d__('system', 'opcache extension');
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
        return ! extension_loaded('Zend OPcache');
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
        return d__('system', 'It is recommended to install the php opcache extension for better performance.');
    }
}
