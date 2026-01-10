<?php

declare(strict_types=1);

namespace Johncms\Config;

final class ConfigRepository
{
    private static array $config = [];

    public static function init(array $config): void
    {
        self::$config = $config;
    }

    public static function all(): array
    {
        return self::$config;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = self::$config;

        $segments = explode('.', $key);
        foreach ($segments as $segment) {
            if (! is_array($value) || ! array_key_exists($segment, $value)) {
                return $default;
            }

            $value = $value[$segment];
        }

        return $value;
    }
}
