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

use RuntimeException;

/**
 * The exchange with an external service did not end in an identity: the visitor refused, the
 * round trip failed its checks, or the provider answered with something unusable.
 */
class ExternalAuthException extends RuntimeException
{
}
