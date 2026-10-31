<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\UseCases;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\Events\AuthEvent;
use Johncms\Modules\Admin\Application\DTO\SecurityOverviewDTO;
use Johncms\Modules\Admin\Application\DTO\StaffSignInDTO;
use Johncms\Modules\Admin\Application\Services\AdminPermissions;
use Johncms\Modules\Admin\Domain\Repository\SecurityOverviewRepositoryInterface;

/**
 * What the dashboard tells about the security of the site over the last day: the sign-ins that
 * were refused and where they came from, who of the staff signed in, the bans.
 */
final readonly class GetSecurityOverviewUseCase
{
    private const int DAY = 86400;
    private const int SUSPICIOUS_IPS = 5;
    private const int STAFF_SIGN_INS = 5;

    public function __construct(
        private AccessCheckerInterface $accessChecker,
        private SecurityOverviewRepositoryInterface $repository,
    ) {
    }

    public function execute(int $now): SecurityOverviewDTO
    {
        $dayAgo = $now - self::DAY;
        $signInLog = $this->accessChecker->allows(AdminPermissions::AUTH_LOG_VIEW);

        return new SecurityOverviewDTO(
            signInLog: $signInLog,
            failedSignIns: $signInLog ? $this->repository->countFailedSignInsSince($dayAgo) : 0,
            suspiciousIps: $signInLog ? $this->repository->getTopFailedIps($dayAgo, self::SUSPICIOUS_IPS) : [],
            staffSignIns: $signInLog ? $this->staffSignIns($now) : [],
            activeBans: $this->repository->countActiveBans($now),
            bansToday: $this->repository->countBansIssuedSince($dayAgo),
        );
    }

    /**
     * @return list<StaffSignInDTO>
     */
    private function staffSignIns(int $now): array
    {
        return array_values($this->repository->getStaffSignIns($now, self::STAFF_SIGN_INS)->map(
            static fn (AuthEvent $event): StaffSignInDTO => new StaffSignInDTO(
                (int) $event->user_id,
                (string) $event->getAttribute('user_name'),
                $event->ip,
                $event->created_at,
            )
        )->all());
    }
}
