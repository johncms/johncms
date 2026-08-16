<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\DTO;

/**
 * One external service linked to the account.
 */
final readonly class LinkedAccountDTO
{
    public function __construct(
        public string $provider,
        public string $label,
        public string $nickname,
        public int $linkedAt,
        /** Whether a provider still answers for this key; false for a module that was removed. */
        public bool $available = true,
    ) {
    }
}
