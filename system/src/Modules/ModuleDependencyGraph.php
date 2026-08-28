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

use Johncms\Modules\Manifest\ModuleManifest;

/**
 * Who needs whom.
 *
 * One question is asked of it: who would break if this module were switched off. The edges are
 * read against their direction — a module names what it requires, and the answer is everything
 * that named this one.
 */
final readonly class ModuleDependencyGraph
{
    /**
     * @param array<string, ModuleManifest> $modules Keyed by module key.
     */
    public function __construct(private array $modules)
    {
    }

    /**
     * The modules that require the given one, directly or through another module.
     *
     * What a refusal to switch something off is built on: the answer names everything that would
     * stop working, not just the module that mentions it.
     *
     * @return list<string>
     */
    public function dependentsOf(string $key): array
    {
        $found = [];
        $queue = [$key];

        while ($queue !== []) {
            $current = array_shift($queue);

            foreach ($this->modules as $candidate => $manifest) {
                if (isset($found[$candidate]) || $candidate === $key) {
                    continue;
                }

                if (! array_key_exists($current, $manifest->requires->modules)) {
                    continue;
                }

                $found[$candidate] = true;
                $queue[] = $candidate;
            }
        }

        $dependents = array_keys($found);
        sort($dependents);

        return $dependents;
    }
}
