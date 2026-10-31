<?php

declare(strict_types=1);

namespace Johncms\Checker\Checks;

use Johncms\Checker\CheckSeverity;
use Johncms\Checker\CheckStatus;
use Johncms\Checker\DatabaseProbeInterface;
use Johncms\Checker\DBChecker;
use Johncms\Checker\DTO\CheckDefinitionDTO;
use Johncms\Checker\DTO\CheckResultDTO;
use Johncms\Checker\SystemCheckInterface;

final readonly class DatabaseVersionCheck implements SystemCheckInterface
{
    public function __construct(private DatabaseProbeInterface $database)
    {
    }

    public static function definition(): CheckDefinitionDTO
    {
        return new CheckDefinitionDTO('database.version', d__('system', 'Version of the database server'), d__('system', 'Database'), 'database', order: 20);
    }

    public function run(): array
    {
        if ($this->database->driver() !== 'mysql') {
            return [new CheckResultDTO(
                'database.version',
                self::definition()->name,
                $this->database->driver(),
                CheckStatus::Skipped,
                CheckSeverity::Info,
                d__('system', 'This check applies to MySQL and MariaDB connections.'),
            )];
        }
        $info = $this->database->versionInfo();

        return [new CheckResultDTO(
            'database.version',
            self::definition()->name,
            $info['version_raw'],
            $info['error'] ? CheckStatus::Failed : CheckStatus::Passed,
            CheckSeverity::Critical,
            d__('system', 'The system requires a MySQL server version %s or higher or MariaDB %s or higher.', DBChecker::MYSQL_VERSION, DBChecker::MARIADB_VERSION),
        )];
    }
}
