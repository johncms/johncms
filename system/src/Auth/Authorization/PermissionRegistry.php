<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Auth\Authorization;

use InvalidArgumentException;

/**
 * Every permission the running installation knows about, gathered from the modules that declare
 * them. It is what the role editor lists; the checks themselves go through AccessCheckerInterface.
 *
 * Providers are read once, on first access, so a request that never opens the role editor does
 * not build them.
 *
 * Definitions can also be passed straight to the constructor. That is not a convenience for
 * production code — it is what lets a test declare the two permissions it cares about without a
 * container behind it.
 */
final class PermissionRegistry
{
    /** @var array<string, PermissionDefinition>|null */
    private ?array $catalogue = null;

    /**
     * @param iterable<PermissionProviderInterface> $providers
     * @param iterable<PermissionDefinition>        $definitions
     */
    public function __construct(
        private readonly iterable $providers = [],
        private readonly iterable $definitions = [],
    ) {
    }

    public function has(string $key): bool
    {
        return isset($this->all()[$key]);
    }

    public function get(string $key): PermissionDefinition
    {
        return $this->all()[$key]
            ?? throw new InvalidArgumentException(sprintf('Unknown permission "%s".', $key));
    }

    /**
     * @return array<string, PermissionDefinition> Keyed by the permission key.
     */
    public function all(): array
    {
        if ($this->catalogue === null) {
            $this->catalogue = $this->collect();
        }

        return $this->catalogue;
    }

    /**
     * The catalogue as the role editor shows it: groups in the order the modules were loaded,
     * each holding its permissions in the order they were declared.
     *
     * @return array<string, list<PermissionDefinition>>
     */
    public function grouped(): array
    {
        $grouped = [];

        foreach ($this->all() as $definition) {
            $grouped[$definition->group][] = $definition;
        }

        return $grouped;
    }

    /**
     * The title of every group, keyed by the group. A module that named its group gets that name;
     * one that did not is listed under the key itself rather than under an empty heading.
     *
     * @return array<string, string>
     */
    public function groupLabels(): array
    {
        $labels = [];

        foreach ($this->all() as $definition) {
            if ($definition->groupLabel !== null) {
                $labels[$definition->group] ??= $definition->groupLabel;
            }
        }

        foreach ($this->all() as $definition) {
            $labels[$definition->group] ??= $definition->group;
        }

        return $labels;
    }

    /**
     * @return array<string, PermissionDefinition>
     */
    private function collect(): array
    {
        $collected = [];

        foreach ($this->providers as $provider) {
            foreach ($provider->permissions() as $definition) {
                $collected[$definition->key] = $definition;
            }
        }

        foreach ($this->definitions as $definition) {
            $collected[$definition->key] = $definition;
        }

        return $collected;
    }
}
