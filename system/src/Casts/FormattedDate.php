<?php

declare(strict_types=1);

namespace Johncms\Casts;

use Carbon\Carbon;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Johncms\Settings\SiteSettings;

class FormattedDate implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes)
    {
        if (! empty($value)) {
            $settings = di(SiteSettings::class);
            return Carbon::createFromTimeString($value)
                ->locale($settings->getLanguage())
                ->timezone($settings->getTimezone())
                ->isoFormat('lll');
        }

        return $value;
    }

    public function set($model, string $key, $value, array $attributes): ?string
    {
        if (! empty($value)) {
            return Carbon::parse($value)->toDateTimeString();
        }

        return null;
    }
}
