<?php

declare(strict_types=1);

namespace Johncms\Checker\Checks;

use Johncms\Checker\CheckSeverity;
use Johncms\Checker\CheckStatus;
use Johncms\Checker\DTO\CheckDefinitionDTO;
use Johncms\Checker\DTO\CheckResultDTO;
use Johncms\Checker\SystemCheckInterface;
use Johncms\Database\Migrations\PendingMigrationsProviderInterface;

final readonly class PendingMigrationsCheck implements SystemCheckInterface
{
    public function __construct(private PendingMigrationsProviderInterface $migrations)
    {
    }

    public static function definition(): CheckDefinitionDTO
    {
        return new CheckDefinitionDTO('database.migrations', d__('system', 'Pending database migrations'), d__('system', 'Database'), 'database', order: 22);
    }

    public function run(): array
    {
        $count = count($this->migrations->pending());

        return [new CheckResultDTO(
            'database.migrations',
            self::definition()->name,
            (string) $count,
            $count > 0 ? CheckStatus::Failed : CheckStatus::Passed,
            CheckSeverity::Critical,
            d__('system', 'Apply pending migrations after updating the site: php system/bin/console migrate. Back up the database before updating it.'),
        )];
    }
}
