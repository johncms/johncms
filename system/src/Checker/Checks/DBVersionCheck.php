<?php

declare(strict_types=1);

namespace Johncms\Checker\Checks;

use Johncms\Checker\CheckInterface;
use Johncms\Checker\DBChecker;
use Johncms\Checker\SystemChecker;

class DBVersionCheck implements CheckInterface
{
    private DBChecker $DBChecker;
    private array $versionInfo;

    public function __construct()
    {
        $this->DBChecker = new DBChecker();
        $this->versionInfo = $this->DBChecker->versionInfo();
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return d__('system', 'Version of the database server');
    }

    /**
     * @inheritDoc
     */
    public function getValue(): string
    {
        return $this->versionInfo['version_raw'];
    }

    /**
     * @inheritDoc
     */
    public function isError(): bool
    {
        return $this->versionInfo['error'];
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
        return d__('system', 'The system requires a MySQL server version %s or higher or MariaDB %s or higher.', DBChecker::MYSQL_VERSION, DBChecker::MARIADB_VERSION);
    }
}
