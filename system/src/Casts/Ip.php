<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class Ip implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param Model $model
     * @param string $key
     * @param int $value
     * @param array $attributes
     * @return string
     */
    public function get($model, $key, $value, $attributes): string
    {
        return ! empty($value) ? long2ip($value) : '';
    }

    /**
     * Prepare the given value for storage.
     *
     * @param Model $model
     * @param string $key
     * @param string $value
     * @param array $attributes
     * @return int
     */
    public function set($model, $key, $value, $attributes): int
    {
        return ! empty($value) && filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) ? ip2long($value) : 0;
    }
}
