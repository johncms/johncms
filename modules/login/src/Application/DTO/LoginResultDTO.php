<?php

declare(strict_types=1);

namespace Johncms\Modules\Login\Application\DTO;

use Johncms\Modules\Login\Domain\Enums\LoginStatus;
use Mobicms\Captcha\Image;

final readonly class LoginResultDTO
{
    public function __construct(
        public LoginStatus $status,
        public array $errors = [],
        public ?int $userId = null,
        public ?string $passwordHash = null,
        public ?Image $captcha = null,
    ) {
    }
}
