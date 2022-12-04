<?php

declare(strict_types=1);

namespace Johncms\Checker\Checks;

use Johncms\Checker\CheckInterface;
use Johncms\Checker\SystemChecker;
use PDO;

class PDOCheck implements CheckInterface
{
    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'PDO';
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
        return ! class_exists(PDO::class);
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
        return d__('system', 'PHP extension PDO must be installed');
    }
}
