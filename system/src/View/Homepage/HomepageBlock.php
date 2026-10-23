<?php

declare(strict_types=1);

namespace Johncms\View\Homepage;

/**
 * A block a module puts on the home page: a template of its own and what to fill it with.
 */
final readonly class HomepageBlock
{
    /**
     * @param string               $template A template of the module, by its namespace — "@news/homepage/latest.twig".
     * @param array<string, mixed> $data     What the template is rendered with, and nothing else.
     * @param int                  $weight   Lighter blocks go higher; equal weights keep the order of the modules.
     */
    public function __construct(
        public string $template,
        public array $data = [],
        public int $weight = 100,
    ) {
    }
}
