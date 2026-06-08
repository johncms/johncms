<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Domain\Enums;

enum IpBanType: int
{
    case BLOCK = 1;
    case REDIRECT = 2;
    case REGISTRATION = 3;

    public static function fromValueOrBlock(int $value): self
    {
        return self::tryFrom($value) ?? self::BLOCK;
    }

    public function label(): string
    {
        return match ($this) {
            self::REDIRECT     => __('Redirect'),
            self::REGISTRATION => __('Registration'),
            self::BLOCK        => __('Block'),
        };
    }
}
