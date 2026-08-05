<?php

declare(strict_types=1);

namespace Johncms\View\Asset;

use Johncms\View\Theme\ThemeChainResolver;
use RuntimeException;

/**
 * Finds the build entry point of an area — the public site or the admin panel — along the theme
 * chain.
 *
 * A layout names the area rather than a path, so the layout of the default theme, inherited by a
 * child theme, loads the bundle of the child. A theme that has no sources of its own leaves its
 * entries out of the manifest and gets the bundle of its parent.
 */
final readonly class ThemeEntryResolver
{
    public function __construct(private ThemeChainResolver $themeChain)
    {
    }

    public function entry(string $area): string
    {
        foreach ($this->themeChain->resolve((string) config('johncms.skindef', 'default')) as $theme) {
            $entry = $theme->entries[$area] ?? null;

            if (is_string($entry) && $entry !== '') {
                return $entry;
            }
        }

        throw new RuntimeException(
            sprintf('No theme in the chain declares the "%s" build entry point.', $area)
        );
    }
}
