<?php

declare(strict_types=1);

namespace Johncms\Utility;

use Carbon\Carbon;
use DateTimeZone;
use Johncms\i18n\Translator;
use Johncms\Settings\SiteSettings;
use Throwable;

class DateTime
{
    /**
     * Get a list of time zones
     *
     * @return array<array{name: string, value: string}>
     */
    public static function getTimezones(): array
    {
        $timezones = DateTimeZone::listIdentifiers();

        return array_map(function ($timezone) {
            return [
                'name' => $timezone,
                'value' => $timezone,
            ];
        }, $timezones);
    }

    /**
     * Format the time depending on the user settings
     */
    public static function userFormat(mixed $time): string
    {
        // TODO: Add user format
        return self::format($time, false, true);
    }

    /**
     * Format the time to default format with date and time
     */
    public static function format(mixed $date, bool $withoutTime = false, bool $withoutSeconds = false)
    {
        if (empty($date)) {
            return '';
        }
        $siteSettings = di(SiteSettings::class);
        try {
            if (is_integer($date)) {
                $date_object = Carbon::createFromTimestamp($date, $siteSettings->getTimezone());
            } else {
                $date_object = Carbon::make($date)->timezone($siteSettings->getTimezone());
            }

            if ($date_object) {
                if ($withoutTime) {
                    return $date_object->format('d.m.Y');
                } elseif ($withoutSeconds) {
                    return $date_object->format('d.m.Y H:i');
                }
                return $date_object->format('d.m.Y H:i:s');
            }
        } catch (Throwable) {
        }

        return '';
    }

    /**
     * Returns either day of week + time (e.g. “Last Friday at 3:30 PM”)
     * if reference time is within 7 days, or a calendar date (e.g. “10/29/2017”) otherwise.
     */
    public static function calendarFormat(mixed $time)
    {
        $translator = di(Translator::class);
        $siteSettings = di(SiteSettings::class);
        try {
            if (is_integer($time)) {
                $date = Carbon::createFromTimestamp($time, $siteSettings->getTimezone());
            } else {
                $date = Carbon::make($time)->timezone($siteSettings->getTimezone());
            }
            return $date->locale($translator->getLocale())
                ->calendar(
                    null,
                    [
                        'lastWeek' => 'lll',
                        'sameElse' => 'lll',
                    ]
                );
        } catch (Throwable) {
        }

        return '';
    }

    /**
     * Format for database
     */
    public static function prepareForDatabase(mixed $time, bool $useTimezone = true): string
    {
        if ($useTimezone) {
            $siteSettings = di(SiteSettings::class);
            return Carbon::parse($time, $siteSettings->getTimezone())->setTimezone('UTC')->toDateTimeString();
        }
        return Carbon::parse($time)->toDateTimeString();
    }
}
