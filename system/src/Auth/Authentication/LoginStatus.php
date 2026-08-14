<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Auth\Authentication;

/**
 * How an attempt to sign in ended.
 *
 * A status rather than a message: the wording differs between the public form and the admin
 * panel, and deciding what happened is not the same job as deciding what to say about it.
 */
enum LoginStatus
{
    case Success;

    /** Wrong login or wrong password — deliberately not distinguished. */
    case InvalidCredentials;

    /** Enough has gone wrong that a verification code is asked for before the password. */
    case CaptchaRequired;

    case CaptchaMismatch;

    /** Guessing has gone far enough that attempts are refused for a while. */
    case TooManyAttempts;

    /** The password was right, but the address has not been confirmed yet. */
    case EmailNotConfirmed;

    /** The password was right, but an administrator has not approved the account yet. */
    case ModerationPending;
}
