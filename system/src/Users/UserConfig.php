<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Users;

class UserConfig
{
    /** @var bool Allow direct external links */
    public bool $directUrl = false;

    public int $perPage = 10;

    public string $lang = 'en';

    public string $timezone = 'UTC';

    public ?string $theme = null;

    public function __construct(array $settings = [])
    {
        foreach ($settings as $key => $value) {
            $this->$key = $this->castValue(gettype($this->$key), $value);
        }
    }

    /**
     * @param string $type
     * @param mixed $value
     * @return bool|float|int|string
     */
    private function castValue(string $type, mixed $value): float|bool|int|string
    {
        return match ($type) {
            'int', 'integer' => (int) $value,
            'float' => (float) $value,
            'string' => (string) $value,
            'bool', 'boolean' => (bool) $value,
            default => $value,
        };
    }
}
