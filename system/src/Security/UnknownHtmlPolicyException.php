<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Security;

use RuntimeException;

/**
 * Asking for a policy nobody declared is a mistake in the code, not a reason to fall back to
 * some other policy: a fallback would either strip content the author expected to keep, or —
 * worse — clean the content by rules that were never meant for it.
 */
final class UnknownHtmlPolicyException extends RuntimeException
{
    /**
     * @param list<string> $known
     */
    public static function forName(string $name, array $known): self
    {
        return new self(
            sprintf(
                'Unknown HTML policy "%s". Declared policies: %s. A module adds its own by '
                . 'registering a service that implements %s.',
                $name,
                $known === [] ? 'none' : implode(', ', $known),
                HtmlPolicyProviderInterface::class
            )
        );
    }
}
