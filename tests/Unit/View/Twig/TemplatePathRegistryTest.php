<?php

declare(strict_types=1);

namespace Tests\Unit\View\Twig;

use Johncms\Modules\Manifest\ModuleManifest;
use Johncms\Modules\ModuleRegistry;
use Johncms\Modules\ModuleRepositoryInterface;
use Johncms\Modules\ModuleStateRecord;
use Johncms\Modules\ModuleStateStore;
use Johncms\View\Theme\FilesystemThemeRepository;
use Johncms\View\Theme\ThemeChainResolver;
use Johncms\View\Twig\TemplatePathProviderInterface;
use Johncms\View\Twig\TemplatePathRegistry;
use PHPUnit\Framework\TestCase;

/**
 * The lookup order every override in the system rests on: what the theme carries wins over its
 * parent, and both win over the module the template belongs to.
 */
final class TemplatePathRegistryTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . DS . 'johncms-paths-' . uniqid() . DS;

        $this->makeDirectories([
            'themes/default/templates',
            'themes/default/templates/admin',
            'themes/base/templates',
            'themes/child/templates',
            'themes/child/templates/news',
            'modules/johncms/news/templates',
            'modules/johncms/admin/templates',
        ]);

        file_put_contents($this->root . 'themes/base/theme.php', "<?php return ['parent' => 'default'];");
        file_put_contents($this->root . 'themes/child/theme.php', "<?php return ['parent' => 'base'];");
    }

    protected function tearDown(): void
    {
        exec('rm -rf ' . escapeshellarg($this->root));
    }

    public function testAModuleNamespaceIsSearchedThroughTheThemeChainBeforeTheModuleItself(): void
    {
        self::assertSame(
            [
                $this->root . 'themes/child/templates/news',
                $this->root . 'modules/johncms/news/templates',
            ],
            $this->normalize($this->registry()->paths('child')['news'])
        );
    }

    /**
     * The registry is built from the installed modules and the theme chain, so a module that
     * carries no templates simply has no namespace — Twig raises on a path that is not there.
     */
    public function testDirectoriesThatDoNotExistAreNotRegistered(): void
    {
        $paths = $this->registry()->paths('child');

        self::assertSame(
            [
                $this->root . 'themes/child/templates',
                $this->root . 'themes/base/templates',
                $this->root . 'themes/default/templates',
            ],
            $this->normalize($paths['theme'])
        );
        // The admin directory only exists in the default theme, and only that one is registered.
        self::assertSame(
            [
                $this->root . 'themes/default/templates/admin',
                $this->root . 'modules/johncms/admin/templates',
            ],
            $this->normalize($paths['admin'])
        );
    }

    /**
     * The admin namespace is the admin subdirectory of the theme, plus the templates of the admin
     * module. Both live under one name, and the theme still comes first.
     */
    public function testTheAdminNamespaceCombinesTheThemeAndTheAdminModule(): void
    {
        self::assertSame(
            [
                $this->root . 'themes/default/templates/admin',
                $this->root . 'modules/johncms/admin/templates',
            ],
            $this->normalize($this->registry()->paths('child')['admin'])
        );
    }

    public function testAProviderAddsPathsBehindTheOnesFromTheConvention(): void
    {
        $this->makeDirectories(['modules/johncms/news/extra-templates']);

        $provider = new class ($this->root) implements TemplatePathProviderInterface {
            public function __construct(private readonly string $root)
            {
            }

            public function paths(): array
            {
                return ['news' => [$this->root . 'modules/johncms/news/extra-templates']];
            }
        };

        self::assertSame(
            [
                $this->root . 'themes/child/templates/news',
                $this->root . 'modules/johncms/news/templates',
                $this->root . 'modules/johncms/news/extra-templates',
            ],
            $this->normalize($this->registry([$provider])->paths('child')['news'])
        );
    }

    /**
     * A namespace is registered for a module the registry loads, and for no other: the templates
     * of a module that was switched off are still on disk, and a page of it answering would be a
     * page of a module this site is not running.
     */
    public function testOnlyTheModulesTheRegistryLoadsGetANamespace(): void
    {
        $manifests = [
            'johncms/news'  => $this->manifest('johncms/news', 'news'),
            'johncms/admin' => $this->manifest('johncms/admin', 'admin'),
        ];

        $modules = new class ($manifests) implements ModuleRepositoryInterface {
            /** @param array<string, ModuleManifest> $manifests */
            public function __construct(private readonly array $manifests)
            {
            }

            public function all(): array
            {
                return $this->manifests;
            }

            public function find(string $key): ?ModuleManifest
            {
                return $this->manifests[$key] ?? null;
            }

            public function forget(): void
            {
            }
        };

        $stateFile = $this->root . 'state.php';
        $store = new ModuleStateStore($stateFile);
        $store->save(['johncms/news' => new ModuleStateRecord('johncms/news', 'news', enabled: false)]);

        $registry = new TemplatePathRegistry(
            new ThemeChainResolver(new FilesystemThemeRepository($this->root . 'themes' . DS)),
            [],
            $this->root . 'themes' . DS,
            null,
            new ModuleRegistry($modules, $store, ['johncms/news', 'johncms/admin']),
        );

        $paths = $registry->paths('child');

        self::assertArrayHasKey('admin', $paths);
        self::assertArrayNotHasKey('news', $paths, 'The switched-off module keeps its files and loses its namespace.');
    }

    /**
     * The namespace is the alias, not the directory: that is what makes a short name possible for
     * a module whose package is called something longer.
     */
    public function testTheNamespaceIsTheAliasOfTheModule(): void
    {
        $this->makeDirectories(['modules/vasya/old-guestbook/templates', 'themes/child/templates/guestbook']);

        $paths = $this->registry([], [$this->manifest('vasya/old-guestbook', 'guestbook')])->paths('child');

        self::assertSame(
            [
                $this->root . 'themes/child/templates/guestbook',
                $this->root . 'modules/vasya/old-guestbook/templates',
            ],
            $this->normalize($paths['guestbook'])
        );
        self::assertArrayNotHasKey('old-guestbook', $paths);
    }

    /**
     * The directory comes from the manifest, so a module Composer put in vendor/ is reachable the
     * same way as one lying in modules/ — nothing moves its files. The theme still comes first,
     * which is what lets a theme override a template of such a module; the pages it does not
     * carry keep falling back to the package.
     */
    public function testAModuleInstalledByComposerIsFoundWhereItLies(): void
    {
        $this->makeDirectories(['vendor/vasya/blog/templates', 'themes/child/templates/blog']);

        $manifest = new ModuleManifest(
            'vasya/blog',
            'blog',
            $this->root . 'vendor' . DS . 'vasya' . DS . 'blog',
            'Blog'
        );

        self::assertSame(
            [
                $this->root . 'themes/child/templates/blog',
                $this->root . 'vendor/vasya/blog/templates',
            ],
            $this->normalize($this->registry([], [$manifest])->paths('child')['blog'])
        );
    }

    /**
     * @param array<TemplatePathProviderInterface> $providers
     * @param array<ModuleManifest>|null           $modules
     */
    private function registry(array $providers = [], ?array $modules = null): TemplatePathRegistry
    {
        return new TemplatePathRegistry(
            new ThemeChainResolver(new FilesystemThemeRepository($this->root . 'themes' . DS)),
            $providers,
            $this->root . 'themes' . DS,
            $modules ?? [
                $this->manifest('johncms/news', 'news'),
                $this->manifest('johncms/admin', 'admin'),
            ],
        );
    }

    private function manifest(string $key, string $alias): ModuleManifest
    {
        return new ModuleManifest(
            $key,
            $alias,
            $this->root . 'modules' . DS . str_replace('/', DS, $key),
            ucfirst($alias)
        );
    }

    /**
     * @param array<string> $paths
     * @return array<string>
     */
    private function normalize(array $paths): array
    {
        return array_map(static fn (string $path): string => str_replace(DS, '/', $path), $paths);
    }

    /**
     * @param array<string> $directories
     */
    private function makeDirectories(array $directories): void
    {
        foreach ($directories as $directory) {
            mkdir($this->root . $directory, 0o777, true);
        }
    }
}
