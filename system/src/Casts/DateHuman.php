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

use Carbon\Carbon;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Johncms\System\i18n\Translator;

class DateHuman implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param Model $model
     * @param string $key
     * @param int $value
     * @param array $attributes
     * @return Carbon|int
     */
    public function get($model, $key, $value, $attributes)
    {
        if (! empty($value)) {
            $translator = di(Translator::class);
            return Carbon::parse($value)
                ->locale($translator->getLocale())
                ->diffForHumans(['join' => false, 'parts' => 2]);
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
