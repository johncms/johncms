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

use Johncms\Auth\Identity;
use Johncms\Http\Request;

/**
 * Turns a request into the identity behind it: the session cookie, an API token, and whatever
 * a later release adds. Implementations are registered with the `johncms.auth.authenticator`
 * tag and asked in turn by AuthenticatorChain.
 *
 * This is the one place in the authentication code that sees a Request, and it does so for the
 * same reason middleware does: reading credentials off the wire is the whole job. Everything
 * below it — use cases, voters, repositories — receives an Identity and never the request.
 */
interface AuthenticatorInterface
{
    /**
     * The identity this authenticator recognises in the request, or null when the request
     * carries nothing it knows about — a missing cookie, no Authorization header. Null means
     * "not mine", so the chain moves on; it does not mean "rejected".
     */
    public function authenticate(Request $request): ?Identity;
}
