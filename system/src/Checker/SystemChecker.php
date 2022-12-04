<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Checker;

use Johncms\Checker\Checks\DBVersionCheck;
use Johncms\Checker\Checks\FileInfoCheck;
use Johncms\Checker\Checks\ImagickCheck;
use Johncms\Checker\Checks\MbstringCheck;
use Johncms\Checker\Checks\MySQLNDCheck;
use Johncms\Checker\Checks\OpcacheCheck;
use Johncms\Checker\Checks\OpcacheEnableCheck;
use Johncms\Checker\Checks\PDOCheck;
use Johncms\Checker\Checks\PHPCheck;
use Johncms\Checker\Checks\ZlibCheck;

class SystemChecker
{
    public const CRITICAL = 1;
    public const WARNING = 2;
    public const INFO = 3;

    public function checkExtensions(): array
    {
        return $this->runChecks(
            [
                PHPCheck::class,
                PDOCheck::class,
                ImagickCheck::class,
                ZlibCheck::class,
                MbstringCheck::class,
                FileInfoCheck::class,
            ]
        );
    }

    public function recommendations(): array
    {
        return $this->runChecks(
            [
                OpcacheCheck::class,
                OpcacheEnableCheck::class,
            ]
        );
    }

    public function checkDatabase(): array
    {
        return $this->runChecks(
            [
                DBVersionCheck::class,
                MySQLNDCheck::class,
            ]
        );
    }

    /**
     * Perform checks
     *
     * @param string[] $checks
     * @return array
     */
    private function runChecks(array $checks): array
    {
        $completedChecks = [];
        foreach ($checks as $check) {
            /** @var CheckInterface $checkClass */
            $checkClass = di($check);

            $completedChecks[] = [
                'name'        => $checkClass->getName(),
                'check_code'  => $check,
                'value'       => $checkClass->getValue(),
                'error'       => $checkClass->isError(),
                'error_level' => $checkClass->getErrorLevel(),
                'description' => $checkClass->getDescription(),
            ];
        }

        return $completedChecks;
    }
}
