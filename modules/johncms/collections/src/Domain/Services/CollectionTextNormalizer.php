<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Domain\Services;

final class CollectionTextNormalizer
{
    public static function normalize(string $value): string
    {
        return mb_strtolower(trim((string) preg_replace('/\s+/u', ' ', $value)), 'UTF-8');
    }
}
