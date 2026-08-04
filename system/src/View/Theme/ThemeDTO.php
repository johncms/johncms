<?php

declare(strict_types=1);

namespace Johncms\View\Theme;

/**
 * A theme as its manifest describes it.
 *
 * The name is the directory under themes/, which is what the configuration and the asset URLs
 * refer to; the title is what a human reads.
 */
final readonly class ThemeDTO
{
    /**
     * @param array<string, string|null> $entries Vite entry points by area; null means the entry
     *                                            of the parent theme is used.
     * @param array<string, mixed>       $settings
     */
    public function __construct(
        public string $name,
        public string $title,
        public ?string $parent = null,
        public array $entries = [],
        public array $settings = [],
    ) {
    }
}
