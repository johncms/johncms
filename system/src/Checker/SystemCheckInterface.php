<?php

declare(strict_types=1);

namespace Johncms\Checker;

use Johncms\Checker\DTO\CheckDefinitionDTO;
use Johncms\Checker\DTO\CheckResultDTO;

/**
 * A module joins the diagnostics by implementing this interface on an autoconfigured service.
 * Metadata must perform no I/O: it is read before deciding whether to instantiate the check.
 * Page, Dashboard and Installer checks are read-only. Extended checks may probe storage with
 * temporary data and must clean up. All checks are bounded; related rows may share a check.
 */
interface SystemCheckInterface
{
    public static function definition(): CheckDefinitionDTO;

    /** @return list<CheckResultDTO> */
    public function run(): array;
}
