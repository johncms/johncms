<?php

declare(strict_types=1);

namespace Johncms\Checker\DTO;

use Johncms\Checker\CheckSeverity;
use Johncms\Checker\CheckStatus;

final readonly class CheckReportDTO
{
    public int $critical;
    public int $warnings;
    public int $unavailable;
    public int $total;
    public int $evaluated;

    /** @param list<array{title: string, icon: string, checks: list<CheckResultDTO>}> $sections */
    public function __construct(
        public array $sections,
        public int $checkedAt,
        public string $runtime,
    ) {
        $critical = $warnings = $unavailable = $total = $evaluated = 0;
        foreach ($sections as $section) {
            foreach ($section['checks'] as $check) {
                $total++;
                if ($check->status === CheckStatus::Passed || $check->status === CheckStatus::Failed) {
                    $evaluated++;
                }
                if ($check->status === CheckStatus::Unavailable) {
                    $unavailable++;
                } elseif ($check->status === CheckStatus::Failed) {
                    $critical += (int) ($check->severity === CheckSeverity::Critical);
                    $warnings += (int) ($check->severity === CheckSeverity::Warning);
                }
            }
        }

        $this->critical = $critical;
        $this->warnings = $warnings;
        $this->unavailable = $unavailable;
        $this->total = $total;
        $this->evaluated = $evaluated;
    }
}
