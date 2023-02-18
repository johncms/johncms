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
use Johncms\Users\UserConfig;
use JsonException;
use Symfony\Component\Serializer\Serializer;

class UserSettings implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param Model $model
     * @param mixed $value
     * @throws JsonException
     */
    public function get($model, string $key, $value, array $attributes): UserConfig
    {
        $settings = [];
        if (! empty($value)) {
            $settings = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        }

        $serializer = di(Serializer::class);

        return $serializer->denormalize($settings, UserConfig::class);
    }

    /**
     * Prepare the given value for storage.
     *
     * @param Model $model
     * @param array $value
     */
    public function set($model, string $key, $value, array $attributes): string
    {
        $serializer = di(Serializer::class);
        return $serializer->serialize($value, 'json');
    }
}
