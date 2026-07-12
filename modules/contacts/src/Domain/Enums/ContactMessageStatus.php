<?php

declare(strict_types=1);

namespace Johncms\Modules\Contacts\Domain\Enums;

enum ContactMessageStatus: string
{
    case New = 'new';
    case Processed = 'processed';

    public static function tryFromString(?string $value): ?self
    {
        if ($value === null || $value === '') {
            return null;
        }

        return self::tryFrom($value);
    }
}
