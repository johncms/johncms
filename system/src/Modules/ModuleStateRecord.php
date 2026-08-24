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
 * What the site records about one module: that it was installed, whether it is switched on, which
 * version went in and when.
 *
 * The alias is stored rather than read from the manifest every time. It is what the journal of
 * migrations and the namespaces of templates were written under, so the record has to hold the
 * value that was true at installation — a manifest edited afterwards must not silently move it.
 */
final readonly class ModuleStateRecord
{
    public function __construct(
        public string $key,
        public string $alias,
        public bool $installed = true,
        public bool $enabled = true,
        public ?string $version = null,
        public ?int $installedAt = null,
    ) {
    }

    /**
     * @return array<string, scalar|null>
     */
    public function toArray(): array
    {
        return [
            'alias'        => $this->alias,
            'installed'    => $this->installed,
            'enabled'      => $this->enabled,
            'version'      => $this->version,
            'installed_at' => $this->installedAt,
        ];
    }
}
