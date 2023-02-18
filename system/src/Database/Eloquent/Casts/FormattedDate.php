<?php

declare(strict_types=1);

namespace Johncms\Database\Eloquent\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Johncms\Utility\DateTime;

class FormattedDate implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes)
    {
        if (! empty($value)) {
            return DateTime::userFormat($value);
        }

        return $value;
    }

    public function set($model, string $key, $value, array $attributes): ?string
    {
        if (! empty($value)) {
            return DateTime::prepareForDatabase($value);
        }

        return null;
    }
}
