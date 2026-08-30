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
        /**
         * The installation of this module was begun and did not finish.
         *
         * Written before the migrations run, because the migrations of a module are only found
         * through a record saying it is installed, and cleared once its installer has returned.
         * Whatever lies between — a migration that failed, a request that timed out on a modest
         * host — leaves it standing, and a module with it standing is not loaded: half a schema
         * behind a full set of routes is how "installed it and got a 500" happens.
         */
        public bool $installing = false,
    ) {
    }

    /**
     * The same record with one or two fields changed. Nothing else about it moves — the alias and
     * the date of installation belong to the installation that happened, not to the edit.
     */
    public function with(?bool $enabled = null, ?string $version = null, ?bool $installing = null): self
    {
        return new self(
            key: $this->key,
            alias: $this->alias,
            installed: $this->installed,
            enabled: $enabled ?? $this->enabled,
            version: $version ?? $this->version,
            installedAt: $this->installedAt,
            installing: $installing ?? $this->installing,
        );
    }

    /**
     * The field of an unfinished installation is written only while it is true, so the state file
     * of a site where nothing went wrong reads exactly as it did before it existed.
     *
     * @return array<string, scalar|null>
     */
    public function toArray(): array
    {
        $fields = [
            'alias'        => $this->alias,
            'installed'    => $this->installed,
            'enabled'      => $this->enabled,
            'version'      => $this->version,
            'installed_at' => $this->installedAt,
        ];

        if ($this->installing) {
            $fields['installing'] = true;
        }

        return $fields;
    }
}
