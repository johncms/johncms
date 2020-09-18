<?php

declare(strict_types=1);

namespace Johncms\Casts;

use Carbon\Carbon;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Johncms\System\i18n\Translator;
use Johncms\System\Users\User;

class FormattedDate implements CastsAttributes
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
            /** @var User $user */
            $user = di(User::class);
            /** @var Translator $translator */
            $translator = di(Translator::class);

            return Carbon::createFromTimeString($value)
                ->addHours($user->config->timeshift)
                ->locale($translator->getLocale())
                ->isoFormat('lll');
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
    public function set($model, $key, $value, $attributes): ?string
    {
        return $value;
    }
}
