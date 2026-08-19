<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Captcha;

/**
 * The settings of one provider, as the site configured them.
 *
 * Read from config when it is asked for rather than injected, for the reason ProviderSettings
 * gives: the panel writes these into captcha.local.php, and a value baked into the compiled
 * container would keep being the old one.
 */
final readonly class CaptchaProviderOptions
{
    /**
     * @param array<string, mixed> $values
     */
    private function __construct(private array $values)
    {
    }

    public static function forProvider(string $key): self
    {
        /** @var array<string, mixed> $values */
        $values = (array) config('captcha.providers.' . $key . '.options', []);

        return new self($values);
    }

    /**
     * The value as it is stored, for a caller that does not know its type in advance — the
     * settings page, which walks the fields a provider declares.
     */
    public function raw(string $key, mixed $default = null): mixed
    {
        return $this->values[$key] ?? $default;
    }

    public function string(string $key, string $default = ''): string
    {
        $value = $this->values[$key] ?? null;

        return is_scalar($value) && (string) $value !== '' ? (string) $value : $default;
    }

    public function int(string $key, int $default = 0): int
    {
        $value = $this->values[$key] ?? null;

        return is_numeric($value) ? (int) $value : $default;
    }

    public function float(string $key, float $default = 0.0): float
    {
        $value = $this->values[$key] ?? null;

        return is_numeric($value) ? (float) $value : $default;
    }

    public function bool(string $key, bool $default = false): bool
    {
        $value = $this->values[$key] ?? null;

        return $value === null ? $default : (bool) $value;
    }
}
