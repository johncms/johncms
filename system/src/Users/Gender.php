<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Users;

/**
 * The genders an account may name, as users.sex stores them. Every form offers these and every
 * check accepts these; an account that named none stores an empty string.
 */
enum Gender: string
{
    case Male = 'm';
    case Female = 'f';

    public function title(): string
    {
        return match ($this) {
            self::Male   => d__('system', 'Man'),
            self::Female => d__('system', 'Woman'),
        };
    }

    /**
     * What users.sex may hold, for the rules of a form.
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $gender): string => $gender->value, self::cases());
    }
}
