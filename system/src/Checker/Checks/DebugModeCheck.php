<?php

declare(strict_types=1);

namespace Johncms\Checker\Checks;

use Johncms\Checker\CheckSeverity;
use Johncms\Checker\CheckStatus;
use Johncms\Checker\DTO\CheckDefinitionDTO;
use Johncms\Checker\DTO\CheckResultDTO;
use Johncms\Checker\SystemCheckInterface;

final readonly class DebugModeCheck implements SystemCheckInterface
{
    public function __construct(private bool $debug = DEBUG, private bool $publicDebug = DEBUG_FOR_ALL)
    {
    }

    public static function definition(): CheckDefinitionDTO
    {
        return new CheckDefinitionDTO('security.debug', d__('system', 'Debug mode'), d__('system', 'Security'), 'shield', order: 81);
    }

    public function run(): array
    {
        return [new CheckResultDTO(
            'security.debug',
            self::definition()->name,
            'DEBUG=' . ($this->debug ? 'true' : 'false') . ', DEBUG_FOR_ALL=' . ($this->publicDebug ? 'true' : 'false'),
            $this->debug ? CheckStatus::Failed : CheckStatus::Passed,
            $this->debug && $this->publicDebug ? CheckSeverity::Warning : CheckSeverity::Info,
            d__('system', 'Debug mode is useful during development. Disable DEBUG on a public production site; DEBUG_FOR_ALL exposes additional diagnostics to visitors.'),
        )];
    }
}
