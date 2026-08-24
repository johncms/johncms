<?php

declare(strict_types=1);

namespace Tests\Unit\Modules;

use Johncms\Modules\Manifest\ModuleManifest;
use Johncms\Modules\Manifest\ModuleRequirements;
use Johncms\Modules\ModuleDependencyGraph;
use PHPUnit\Framework\TestCase;

/**
 * The same edges read in both directions: in which order modules may be installed, and who breaks
 * if this one is switched off.
 */
final class ModuleDependencyGraphTest extends TestCase
{
    public function testWhatAModuleNeedsIsInstalledBeforeIt(): void
    {
        $graph = $this->graph([
            'vasya/blog'     => ['vasya/core' => '^1.0'],
            'vasya/core'     => [],
            'vasya/comments' => ['vasya/blog' => '^1.0'],
        ]);

        self::assertSame(
            ['vasya/core', 'vasya/blog', 'vasya/comments'],
            $graph->installationOrder(['vasya/comments', 'vasya/blog', 'vasya/core'])
        );
    }

    /**
     * Modules that know nothing of each other keep their alphabetical order, so two runs of the
     * same installation do the same thing.
     */
    public function testUnrelatedModulesKeepADeterministicOrder(): void
    {
        $graph = $this->graph(['b/two' => [], 'a/one' => [], 'c/three' => []]);

        self::assertSame(['a/one', 'b/two', 'c/three'], $graph->installationOrder(['c/three', 'b/two', 'a/one']));
    }

    /**
     * A dependency outside the set being installed is not ordered against — it is either already
     * there or missing, and that is for the compatibility check to say.
     */
    public function testADependencyOutsideTheSetIsIgnoredByTheOrder(): void
    {
        $graph = $this->graph(['vasya/blog' => ['johncms/forum' => '^10.0']]);

        self::assertSame(['vasya/blog'], $graph->installationOrder(['vasya/blog']));
    }

    /**
     * A cycle has no correct order, so none is claimed: every module is returned exactly once and
     * the walk ends. An installer running that list fails on the first module with an error naming
     * what is missing — which is the honest outcome for two modules that require each other.
     */
    public function testACycleIsOrderedWithoutHangingOrLosingAModule(): void
    {
        $graph = $this->graph([
            'a/one' => ['b/two' => '^1.0'],
            'b/two' => ['a/one' => '^1.0'],
        ]);

        $order = $graph->installationOrder(['a/one', 'b/two']);

        sort($order);
        self::assertSame(['a/one', 'b/two'], $order);
    }

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
