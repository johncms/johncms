<?php

declare(strict_types=1);

namespace Johncms\Checker\Checks;

use Johncms\Checker\CheckSeverity;
use Johncms\Checker\CheckStatus;
use Johncms\Checker\DatabaseProbeInterface;
use Johncms\Checker\DTO\CheckDefinitionDTO;
use Johncms\Checker\DTO\CheckResultDTO;
use Johncms\Checker\SystemCheckInterface;

final readonly class DatabaseTypesCheck implements SystemCheckInterface
{
    public function __construct(private DatabaseProbeInterface $database)
    {
    }

    public static function definition(): CheckDefinitionDTO
    {
        return new CheckDefinitionDTO('database.types', d__('system', 'mysqlnd test'), d__('system', 'Database'), 'database', order: 21);
    }

    public function run(): array
    {
        if ($this->database->driver() !== 'mysql') {
            return [new CheckResultDTO(
                'database.types',
                self::definition()->name,
                $this->database->driver(),
                CheckStatus::Skipped,
                CheckSeverity::Info,
                d__('system', 'This check applies to MySQL and MariaDB connections.'),
            )];
        }
        $valid = $this->database->checkMysqlnd();

        return [new CheckResultDTO(
            'database.types',
            self::definition()->name,
            $valid ? d__('system', 'Yes') : d__('system', 'No'),
            $valid ? CheckStatus::Passed : CheckStatus::Failed,
            CheckSeverity::Critical,
            d__('system', 'Database integers must be returned as integers. Enable MySQL Native Driver (mysqlnd) and check the PDO connection settings.'),
            'https://www.php.net/manual/en/intro.mysqlnd.php',
        )];
    }
}
