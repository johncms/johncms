<?php

declare(strict_types=1);

namespace Johncms\Modules\Auth\Application\DTO;

final readonly class RegistrationFormDTO
{
    public function __construct(
        public string $name,
        public string $nameLat,
        public string $password,
        public string $sex,
        public string $imname,
        public string $about,
        public string $email,
    ) {
    }
}
