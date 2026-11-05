<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Services;

final readonly class CollectionSelectOptions
{
    /** @param array<string, mixed> $settings */
    public static function label(array $settings, string $code): string
    {
        foreach ($settings['options'] ?? [] as $option) {
            if (($option['code'] ?? null) === $code && is_string($option['label'] ?? null)) {
                return $option['label'];
            }
        }
        return $code;
    }
}
