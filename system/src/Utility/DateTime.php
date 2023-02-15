<?php

declare(strict_types=1);

namespace Johncms\Utility;

use DateTimeZone;

class DateTime
{
    /**
     * @return array<array{name: string, value: string}>
     */
    public static function getTimezones(): array
    {
        $timezones = DateTimeZone::listIdentifiers();

        return array_map(function ($timezone) {
            return [
                'name'  => $timezone,
                'value' => $timezone,
            ];
        }, $timezones);
    }
}
