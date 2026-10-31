<?php

declare(strict_types=1);

namespace Johncms\View\Dashboard;

/**
 * One thing on the dashboard of the panel the administrator has to do or know about: a line
 * leading to where it is dealt with.
 */
final readonly class AttentionItem
{
    /**
     * @param string   $icon  A name in the sprite of the panel; empty for the icon of the level.
     * @param int|null $count How many of it wait; null for a line that has no number to tell.
     */
    public function __construct(
        public string $name,
        public string $url,
        public AttentionLevel $level,
        public ?int $count = null,
        public string $icon = '',
    ) {
    }
}
