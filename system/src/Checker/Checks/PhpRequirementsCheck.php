<?php

declare(strict_types=1);

namespace Johncms\Checker\Checks;

use Composer\Semver\Semver;
use Johncms\Checker\CheckProfile;
use Johncms\Checker\CheckSeverity;
use Johncms\Checker\CheckStatus;
use Johncms\Checker\DTO\CheckDefinitionDTO;
use Johncms\Checker\DTO\CheckResultDTO;
use Johncms\Checker\PhpEnvironmentInterface;
use Johncms\Checker\PlatformRequirements;
use Johncms\Checker\SystemCheckInterface;

final readonly class PhpRequirementsCheck implements SystemCheckInterface
{
    public function __construct(private PhpEnvironmentInterface $php)
    {
    }

    public static function definition(): CheckDefinitionDTO
    {
        return new CheckDefinitionDTO(
            'php.requirements',
            d__('system', 'PHP requirements'),
            d__('system', 'Required parameters'),
            'package',
            [CheckProfile::Page, CheckProfile::Dashboard, CheckProfile::Installer],
            10,
        );
    }

    public function run(): array
    {
        $version = $this->php->version();
        $results = [new CheckResultDTO(
            'php.version',
            d__('system', 'PHP version'),
            $version,
            Semver::satisfies($version, PlatformRequirements::PHP) ? CheckStatus::Passed : CheckStatus::Failed,
            CheckSeverity::Critical,
            d__('system', 'PHP must satisfy the version requirement %s.', PlatformRequirements::PHP),
        )];

        foreach ([...PlatformRequirements::EXTENSIONS, ...PlatformRequirements::RUNTIME_EXTENSIONS] as $extension) {
            $loaded = $this->php->extensionLoaded($extension);
            $results[] = new CheckResultDTO(
                'php.extension.' . $extension,
                $extension,
                $loaded ? d__('system', 'Yes') : d__('system', 'No'),
                $loaded ? CheckStatus::Passed : CheckStatus::Failed,
                CheckSeverity::Critical,
                d__('system', 'The PHP extension %s must be installed and enabled for the web server.', $extension),
            );
        }

        return $results;
    }
}
