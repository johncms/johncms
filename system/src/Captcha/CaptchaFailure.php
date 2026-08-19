<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Captcha;

/**
 * Why a submission did not get through.
 *
 * Kept apart from "wrong answer" on purpose: a service that could not be reached must not be
 * reported to the visitor as a mistake of theirs, and an administrator reading the log has to be
 * able to tell the two apart.
 */
enum CaptchaFailure: string
{
    /** Nothing was submitted: an unanswered picture, or a widget that never ran. */
    case Missing = 'missing';

    /** Answered, but wrongly. */
    case Mismatch = 'mismatch';

    /** The challenge is gone — the session was renewed, or the answer is a second attempt at one. */
    case Expired = 'expired';

    /** The remote service considers the visitor a bot without offering them anything to solve. */
    case LowScore = 'low_score';

    /** The remote service refused the token or could not be reached at all. */
    case Unavailable = 'unavailable';

    /** The provider is missing the keys it needs, so nothing could be checked. */
    case NotConfigured = 'not_configured';

    /**
     * What the visitor is told. The message a rule was constructed with wins over this one.
     */
    public function message(): string
    {
        return match ($this) {
            self::Missing,
            self::Mismatch => d__('system', 'The security code is not correct'),
            self::Expired => d__('system', 'The verification has expired, please try again'),
            self::LowScore => d__('system', 'The verification did not pass, please try again'),
            self::Unavailable,
            self::NotConfigured => d__('system', 'The verification service is unavailable, please try again later'),
        };
    }
}
