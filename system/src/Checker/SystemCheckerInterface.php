<?php

declare(strict_types=1);

namespace Johncms\Checker;

use Johncms\Checker\DTO\CheckReportDTO;

interface SystemCheckerInterface
{
    public function run(CheckProfile $profile = CheckProfile::Page): CheckReportDTO;
}
