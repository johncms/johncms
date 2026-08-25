<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Modules;

/**
 * What the layout has to load because of the modules that are switched on.
 *
 * Published files alone do nothing — something has to put them on the page, and the page belongs
 * to the theme. This is the list the layout prints, so a module can add a script without anybody
 * editing a template of the theme.
 */
final readonly class ModuleAssetRegistry
{
    public function __construct(private ModuleRegistry $registry)
    {
    }

    /**
     * URLs of the files the modules declare for an area, in the order the modules load in — by
     * module key, so a page asks for them in the same order on every request.
     *
     * @return list<string>
     */
    public function urls(string $area): array
    {
        $urls = [];
        foreach ($this->registry->enabled() as $key => $manifest) {
            foreach ($manifest->assets->entriesFor($area) as $asset) {
                $urls[] = $this->url($manifest->alias, $asset, $manifest->version);
            }
        }

        return $urls;
    }

    /**
     * The address one published file is reachable at.
     *
     * The version of the module is the cache buster: a browser holding the previous file must not
     * keep it after an update, and a module has no build hashes of its own to rely on. Asked for
     * an alias without a version — which is how a template of a module asks — the version is
     * looked up; a module of the release has none of its own and gets the version of the CMS.
     */
    public function url(string $alias, string $asset, ?string $version = null): string
    {
        $version ??= $this->versionOf($alias);

        return '/modules/' . $alias . '/' . ltrim($asset, '/') . '?v=' . rawurlencode($version);
    }

    private function versionOf(string $alias): string
    {
        foreach ($this->registry->states() as $state) {
            if ($state->alias === $alias) {
                return $state->version ?? CMS_VERSION;
            }
        }

        return CMS_VERSION;
    }
}
