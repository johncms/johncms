<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\DTO;

/**
 * A sign-in of somebody of the staff, as the security block of the dashboard lists it.
 */
final readonly class StaffSignInDTO
{
    public function __construct(
        public int $userId,
        public string $name,
        public string $ip,
        public int $time,
    ) {
    }
}
