<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Mail\Exception;

use RuntimeException;

final class InvalidEmailAddressException extends RuntimeException
{
    public static function forAddress(string $address): self
    {
        return new self(sprintf('"%s" is not an email address a mail server would accept.', $address));
    }
}
