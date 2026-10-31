<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\DTO;

/**
 * The security block of the dashboard. What comes from the sign-in log is there only for whoever
 * may read the log; the bans are for everybody who may open the panel.
 */
final readonly class SecurityOverviewDTO
{
    /**
     * @param bool                 $signInLog     Whether the visitor may read the sign-in log, and so
     *                                            whether the three fields after it say anything.
     * @param array<string, int>   $suspiciousIps The refused attempts of the day by address, the busiest first.
     * @param list<StaffSignInDTO> $staffSignIns  The newest first.
     */
    public function __construct(
        public bool $signInLog,
        public int $failedSignIns,
        public array $suspiciousIps,
        public array $staffSignIns,
        public int $activeBans,
        public int $bansToday,
    ) {
    }
}
