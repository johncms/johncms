<?php

declare(strict_types=1);

namespace Johncms\Checker\Checks;

use Johncms\Checker\CheckProfile;
use Johncms\Checker\CheckSeverity;
use Johncms\Checker\CheckStatus;
use Johncms\Checker\DTO\CheckDefinitionDTO;
use Johncms\Checker\DTO\CheckResultDTO;
use Johncms\Checker\PhpEnvironmentInterface;
use Johncms\Checker\SystemCheckInterface;

final readonly class OpcacheCheck implements SystemCheckInterface
{
    public function __construct(private PhpEnvironmentInterface $php)
    {
    }

    public static function definition(): CheckDefinitionDTO
    {
        return new CheckDefinitionDTO(
            'php.opcache',
            'OPcache',
            d__('system', 'Recommended parameters'),
            'gauge',
            [CheckProfile::Page, CheckProfile::Dashboard],
            30,
        );
    }

    public function run(): array
    {
        $loaded = $this->php->extensionLoaded('Zend OPcache');
        $enabled = filter_var($this->php->ini('opcache.enable'), FILTER_VALIDATE_BOOL);
        $cli = $this->php->sapi() === 'cli';
        if ($cli) {
            $enabled = $enabled && filter_var($this->php->ini('opcache.enable_cli'), FILTER_VALIDATE_BOOL);
        }

        return [
            new CheckResultDTO(
                'php.opcache.loaded',
                d__('system', 'opcache extension'),
                $loaded ? d__('system', 'Yes') : d__('system', 'No'),
                $loaded ? CheckStatus::Passed : CheckStatus::Failed,
                CheckSeverity::Warning,
                d__('system', 'It is recommended to install the php opcache extension for better performance.'),
            ),
            new CheckResultDTO(
                'php.opcache.enabled',
                $cli ? 'opcache.enable / opcache.enable_cli' : 'opcache.enable',
                $enabled ? d__('system', 'Yes') : d__('system', 'No'),
                ! $loaded ? CheckStatus::Skipped : ($enabled ? CheckStatus::Passed : CheckStatus::Failed),
                $cli ? CheckSeverity::Info : CheckSeverity::Warning,
                $cli
                    ? d__('system', 'This result describes PHP CLI. Open this page in a browser to check the web server OPcache settings.')
                    : d__('system', 'It is recommended to enable the php opcache extension to improve performance.'),
                'https://www.php.net/manual/en/opcache.configuration.php',
            ),
        ];
    }
}
