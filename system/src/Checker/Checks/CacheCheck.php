<?php

declare(strict_types=1);

namespace Johncms\Checker\Checks;

use Johncms\Cache\CacheDriver;
use Johncms\Cache\CacheSettings;
use Johncms\Checker\CacheProbeInterface;
use Johncms\Checker\CheckProfile;
use Johncms\Checker\CheckSeverity;
use Johncms\Checker\CheckStatus;
use Johncms\Checker\DTO\CheckDefinitionDTO;
use Johncms\Checker\DTO\CheckResultDTO;
use Johncms\Checker\SystemCheckInterface;

final readonly class CacheCheck implements SystemCheckInterface
{
    public function __construct(private CacheSettings $settings, private CacheProbeInterface $probe)
    {
    }

    public static function definition(): CheckDefinitionDTO
    {
        return new CheckDefinitionDTO('cache.round_trip', d__('system', 'Cache read and write'), d__('system', 'Cache and storage'), 'database', [CheckProfile::Extended], 70);
    }

    public function run(): array
    {
        if ($this->settings->driver === CacheDriver::Disabled) {
            return [new CheckResultDTO(
                'cache.round_trip',
                self::definition()->name,
                $this->settings->driver->value,
                CheckStatus::Skipped,
                CheckSeverity::Info,
                d__('system', 'The application cache is disabled in configuration.'),
            )];
        }
        $works = $this->probe->roundTrip();

        return [new CheckResultDTO(
            'cache.round_trip',
            self::definition()->name,
            $this->settings->driver->value,
            $works ? CheckStatus::Passed : CheckStatus::Failed,
            CheckSeverity::Critical,
            $this->settings->driver === CacheDriver::Memory
                ? d__('system', 'The array cache lasts for this request only. Use a persistent cache driver on a production site.')
                : d__('system', 'A temporary cache entry was written, read back and removed. If the check fails, inspect the configured cache driver and its access permissions.'),
        )];
    }
}
