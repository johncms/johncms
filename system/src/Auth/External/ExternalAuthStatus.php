<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Auth\External;

/**
 * What came of an identity handed back by a provider.
 */
enum ExternalAuthStatus
{
    /** The identity belongs to an account of the site; the caller opens a session. */
    case SignedIn;

    /**
     * Nobody here yet. The provider rarely gives everything an account needs — VK may withhold
     * the address, and the display name is almost never a free login — so the visitor finishes
     * the profile before an account exists.
     */
    case NeedsProfile;

    /** Registration is closed on this site, so a provider may let existing accounts in and no more. */
    case RegistrationClosed;
}
