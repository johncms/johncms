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
 * Two questions are asked of it, and they are the same edges read in opposite directions: in which
 * order a set of modules may be installed, and who would break if this one were switched off.
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

    /**
     * The order the given modules may be installed in: everything a module requires comes before
     * it. Modules that require nothing of each other keep their alphabetical order, so the answer
     * is the same on every run.
     *
     * A cycle cannot be ordered. It is reported rather than broken silently — the modules of a
     * cycle are appended at the end, where an installer will fail on the first of them with an
     * error naming what is missing.
     *
     * @param list<string> $keys
     * @return list<string>
     */
    public function installationOrder(array $keys): array
    {
        sort($keys);

        $ordered = [];
        $visiting = [];

        foreach ($keys as $key) {
            $this->visit($key, $keys, $ordered, $visiting);
        }

        return array_keys($ordered);
    }

    /**
     * @param list<string>          $keys
     * @param array<string, true>   $ordered
     * @param array<string, true>   $visiting
     */
    private function visit(string $key, array $keys, array &$ordered, array &$visiting): void
    {
        if (isset($ordered[$key]) || isset($visiting[$key])) {
            return;
        }

        $visiting[$key] = true;

        $manifest = $this->modules[$key] ?? null;
        if ($manifest !== null) {
            $required = array_keys($manifest->requires->modules);
            sort($required);

            foreach ($required as $dependency) {
                if (in_array($dependency, $keys, true)) {
                    $this->visit($dependency, $keys, $ordered, $visiting);
                }
            }
        }

        unset($visiting[$key]);

        $ordered[$key] = true;
    }
}
