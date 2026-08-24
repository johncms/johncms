<?php

declare(strict_types=1);

namespace Tests\Unit\View\Twig;

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
     * @param array<TemplatePathProviderInterface> $providers
     */
    private function registry(array $providers = []): TemplatePathRegistry
    {
        return new TemplatePathRegistry(
            new ThemeChainResolver(new FilesystemThemeRepository($this->root . 'themes' . DS)),
            $providers,
            $this->root . 'themes' . DS,
            $this->root . 'modules' . DS,
            ['johncms/news', 'johncms/admin'],
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
