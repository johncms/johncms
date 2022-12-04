<?php

declare(strict_types=1);

namespace Johncms\Checker\Checks;

use Johncms\Checker\CheckInterface;
use Johncms\Checker\DBChecker;
use Johncms\Checker\SystemChecker;

class MySQLNDCheck implements CheckInterface
{
    private DBChecker $DBChecker;
    private bool $mysqlndCheck;

    public function __construct()
    {
        $this->DBChecker = new DBChecker();
        $this->mysqlndCheck = $this->DBChecker->checkMysqlnd();
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return d__('system', 'mysqlnd test');
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
        return ! $this->mysqlndCheck;
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
        return d__(
            'system',
            'We use strict data type checks when developing the system. The CMS will not work correctly if the driver for working with the database returns incorrect data types.
PHP should work with the default driver: <a href="https://www.php.net/manual/en/intro.mysqlnd.php" target="_blank">MySQL Native Driver (mysqlnd)</a>.'
        );
    }
}
