<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Users\Bans;

use RuntimeException;

/**
 * A ban was not given or not lifted, for the reason the message says.
 */
final class BanException extends RuntimeException
{
}
