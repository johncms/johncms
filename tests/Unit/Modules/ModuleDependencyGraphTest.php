<?php

declare(strict_types=1);

namespace Tests\Unit\Modules;

use Johncms\Modules\Manifest\ModuleManifest;
use Johncms\Modules\Manifest\ModuleRequirements;
use Johncms\Modules\ModuleDependencyGraph;
use PHPUnit\Framework\TestCase;

/**
 * The edges read against their direction: who breaks if this module is switched off.
 */
final class ModuleDependencyGraphTest extends TestCase
{
    /**
     * What a refusal to switch something off is built on: everything that would stop working, not
     * only the module that names it.
     */
    public function testDependentsAreFoundThroughTheWholeChain(): void
    {
        $graph = $this->graph([
            'johncms/forum'   => [],
            'johncms/admin'   => ['johncms/forum' => '^10.0'],
            'johncms/reports' => ['johncms/admin' => '^10.0'],
            'johncms/news'    => [],
        ]);

        self::assertSame(['johncms/admin', 'johncms/reports'], $graph->dependentsOf('johncms/forum'));
        self::assertSame([], $graph->dependentsOf('johncms/news'));
    }

    /**
     * @param array<string, array<string, string>> $modules Key to its required modules.
     */
    private function graph(array $modules): ModuleDependencyGraph
    {
        $manifests = [];
        foreach ($modules as $key => $requires) {
            $manifests[$key] = new ModuleManifest(
                key: $key,
                alias: str_replace('/', '.', $key),
                path: MODULES_PATH . $key,
                name: ucfirst(basename($key)),
                requires: new ModuleRequirements(modules: $requires),
            );
        }

        return new ModuleDependencyGraph($manifests);
    }
}
