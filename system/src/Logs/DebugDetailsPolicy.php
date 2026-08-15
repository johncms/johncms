<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Logs;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\Authorization\CorePermissions;
use Psr\Container\ContainerInterface;
use Throwable;

/**
 * Decides whether the details of a failure may be shown to the current visitor.
 *
 * Extracted from GlobalErrorHandler so the kernel answers 500 with exactly the
 * same visibility rule: DEBUG alone is not enough — the shipped config/constants.php has
 * DEBUG = true, so showing details on DEBUG alone would leak stack traces to every visitor.
 *
 * The checker is taken from the container rather than injected: this class answers while a
 * failure is being handled, and that failure may be the container itself.
 */
final readonly class DebugDetailsPolicy
{
    public function __construct(private ContainerInterface $container)
    {
    }

    public function allowed(): bool
    {
        // CONSOLE_MODE, not the CLI SAPI: a RoadRunner or Swoole worker also runs on the CLI SAPI,
        // and there every visitor would get the stack trace of a failure.
        if ((defined('CONSOLE_MODE') && CONSOLE_MODE) || DEBUG_FOR_ALL) {
            return true;
        }

        if (! DEBUG) {
            return false;
        }

        try {
            return (bool) $this->container->get(AccessCheckerInterface::class)
                ?->allows(CorePermissions::SYSTEM_DEBUG_VIEW);
        } catch (Throwable) {
            // The failure may well be the container itself; without an answer nobody sees the
            // details.
            return false;
        }
    }
}
