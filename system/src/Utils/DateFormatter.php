<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Utils;

use Carbon\Carbon;
use Johncms\System\i18n\Translator;
use Johncms\Auth\CurrentUser;

final readonly class DateFormatter implements DateFormatterInterface
{
    public function __construct(
        private CurrentUser $currentUser,
        private Translator $translator,
    ) {
    }

    public function format(int $timestamp): string
    {
        $shift = (int) config('johncms.timeshift', 0) + $this->currentUser->user()->config->timeshift;

        return Carbon::createFromTimestamp($timestamp, $shift)
            ->locale($this->translator->getLocale())
            ->calendar(
                null,
                [
                    'lastWeek' => 'lll',
                    'sameElse' => 'lll',
                ]
            );
    }
}
