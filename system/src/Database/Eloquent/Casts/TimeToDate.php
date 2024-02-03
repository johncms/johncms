<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Database\Eloquent\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Johncms\Utility\DateTime;

class TimeToDate implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param Model $model
     * @param int $value
     */
    public function get($model, string $key, $value, array $attributes): string | int
    {
        if (! empty($value)) {
            return DateTime::userFormat($value);
        }

        return $value;
    }

    /**
     * Prepare the given value for storage.
     *
     * @param Model $model
     * @param string $key
     * @param $value
     * @param array $attributes
     * @return string|null
     */
    public function set($model, $key, $value, $attributes)
    {
        return $value;
    }
}
