<?php

declare(strict_types=1);

namespace Johncms\Notifications;

/**
 * One thing waiting for the visitor: a line of the notifications page leading to where it is, and a
 * number the badge of the header adds up.
 */
final readonly class SystemNotification
{
    /**
     * @param string $key       The name the number goes under in what the notifications() function
     *                          of the templates returns.
     * @param bool   $countShown False for a line that has no number to tell — a ban — while it still
     *                          counts in the badge of the header.
     */
    public function __construct(
        public string $key,
        public string $name,
        public string $url,
        public int $count,
        public bool $countShown = true,
    ) {
    }
}
