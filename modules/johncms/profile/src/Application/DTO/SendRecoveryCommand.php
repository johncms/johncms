<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\DTO;

final readonly class SendRecoveryCommand
{
    public function __construct(
        public string $nick,
        public string $email,
    ) {
    }
}
