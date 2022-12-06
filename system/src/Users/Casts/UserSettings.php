<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Users\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Johncms\Users\UserConfig;

class UserSettings implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param Model $model
     * @param mixed $value
     */
    public function get($model, string $key, $value, array $attributes): UserConfig
    {
        $settings = [];
        if (! empty($value)) {
            $settings = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        }
        return new UserConfig($settings);
    }

    /**
     * Prepare the given value for storage.
     *
     * @param Model $model
     * @param array $value
     */
    public function set($model, string $key, $value, array $attributes): string
    {
        return json_encode((new Collection($value))->toArray(), JSON_THROW_ON_ERROR);
    }
}
