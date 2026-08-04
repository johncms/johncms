<?php

declare(strict_types=1);

namespace Tests\Unit\View\Theme;

use Johncms\View\Theme\FilesystemThemeRepository;
use Johncms\View\Theme\ThemeChainResolver;
use PHPUnit\Framework\TestCase;

/**
 * The fallback chain a template is searched along. A theme carrying a single overridden file
 * depends on it entirely, so does the ability to build a theme on top of another one.
 */
final class ThemeChainTest extends TestCase
{
    private string $themesPath;

    protected function setUp(): void
    {
        $this->themesPath = sys_get_temp_dir() . DS . 'johncms-themes-' . uniqid() . DS;
        mkdir($this->themesPath);
    }

    protected function tearDown(): void
    {
        foreach ((array) glob($this->themesPath . '*') as $directory) {
            @unlink((string) $directory . DS . 'theme.php');
            @rmdir((string) $directory);
        }

        @rmdir($this->themesPath);
    }

    public function testAChainIsWalkedThroughEveryParentDownToTheDefaultTheme(): void
    {
        $this->createTheme('default');
        $this->createTheme('base', ['parent' => 'default']);
        $this->createTheme('child', ['parent' => 'base']);

        self::assertSame(['child', 'base', 'default'], $this->chainOf('child'));
    }

    /**
     * A theme shipped before manifests existed has no theme.php. It still has to fall back to the
     * default theme, which is what such a theme always did.
     */
    public function testAThemeWithoutAManifestInheritsFromTheDefaultTheme(): void
    {
        $this->createTheme('default');
        mkdir($this->themesPath . 'legacy');

        self::assertSame(['legacy', 'default'], $this->chainOf('legacy'));
    }

    public function testAThemeNamedInTheConfigurationButNotInstalledFallsBackToTheDefaultTheme(): void
    {
        $this->createTheme('default');

        self::assertSame(['default'], $this->chainOf('there-is-no-such-theme'));
    }

    /**
     * A hand-written manifest can name a parent that names it back. The walk stops on what it has
     * rather than looping forever, and the default theme still closes the chain.
     */
    public function testAParentLoopDoesNotHangTheResolver(): void
    {
        $this->createTheme('default');
        $this->createTheme('one', ['parent' => 'two']);
        $this->createTheme('two', ['parent' => 'one']);

        self::assertSame(['one', 'two', 'default'], $this->chainOf('one'));
    }

    public function testTheManifestOfTheShippedExampleThemeBuildsTheDocumentedChain(): void
    {
        $resolver = new ThemeChainResolver(new FilesystemThemeRepository(THEMES_PATH));

        self::assertSame(['example', 'default'], array_column($resolver->resolve('example'), 'name'));
    }

    /**
     * @return array<string>
     */
    private function chainOf(string $theme): array
    {
        $resolver = new ThemeChainResolver(new FilesystemThemeRepository($this->themesPath));

        return array_column($resolver->resolve($theme), 'name');
    }

    /**
     * @param array<string, mixed> $manifest
     */
    private function createTheme(string $name, array $manifest = []): void
    {
        mkdir($this->themesPath . $name);
        file_put_contents(
            $this->themesPath . $name . DS . 'theme.php',
            '<?php return ' . var_export($manifest, true) . ';'
        );
    }
}
