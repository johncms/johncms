<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Modules\Manifest;

/**
 * The web-accessible files a module ships, and which of them belong on a page.
 *
 * They are shipped **built**. There is no Vite on a site that is merely running the CMS — node is
 * not something a shared host has — so a module that needs a bundle brings the bundle.
 */
final readonly class ModuleAssets
{
    /**
     * @param string                       $source  Directory inside the module holding the files.
     * @param array<string, list<string>>  $entries Files to put on a page, by area: "public" or
     *                                              "admin". Paths are relative to the source
     *                                              directory.
     */
    public function __construct(
        public string $source = 'public',
        public array $entries = [],
    ) {
    }

    /**
     * @return list<string>
     */
    public function entriesFor(string $area): array
    {
        return $this->entries[$area] ?? [];
    }
}
