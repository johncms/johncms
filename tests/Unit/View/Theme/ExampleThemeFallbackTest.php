<?php

declare(strict_types=1);

namespace Tests\Unit\View\Theme;

use Johncms\Modules\Manifest\ModuleManifest;
use Johncms\View\Theme\FilesystemThemeRepository;
use Johncms\View\Theme\ThemeChainResolver;
use Johncms\View\Twig\TemplatePathRegistry;
use PHPUnit\Framework\TestCase;
use Twig\Loader\FilesystemLoader;

/**
 * The theme shipped as the example of an override, checked against the real tree: it carries one
 * template and nothing else, and everything around that template has to come from the default
 * theme.
 *
 * It is the cheapest end-to-end check of the fallback chain there is, which is why it is kept
 * green from the pilot onwards.
 */
final class ExampleThemeFallbackTest extends TestCase
{
    public function testTheExampleThemeOverridesOnlyTheHomePage(): void
    {
        $loader = $this->loader('example');

        self::assertSame(
            THEMES_PATH . 'example' . DS . 'templates' . DS . 'homepage' . DS . 'public' . DS . 'index.twig',
            $loader->getSourceContext('@homepage/public/index.twig')->getPath()
        );

        // The layout, the components and everything else it does not carry come from the default
        // theme, without the theme naming it anywhere.
        self::assertSame(
            THEMES_PATH . 'default' . DS . 'templates' . DS . 'layouts' . DS . 'default.twig',
            $loader->getSourceContext('@theme/layouts/default.twig')->getPath()
        );
    }

    public function testTheDefaultThemeServesTheTemplateOfTheModuleItself(): void
    {
        self::assertSame(
            MODULES_PATH . 'johncms' . DS . 'homepage' . DS . 'templates' . DS . 'public' . DS . 'index.twig',
            $this->loader('default')->getSourceContext('@homepage/public/index.twig')->getPath()
        );
    }

    private function loader(string $theme): FilesystemLoader
    {
        $registry = new TemplatePathRegistry(
            themeChain: new ThemeChainResolver(new FilesystemThemeRepository()),
            modules: [
                new ModuleManifest('johncms/homepage', 'homepage', MODULES_PATH . 'johncms/homepage', 'Homepage'),
            ],
        );

        $loader = new FilesystemLoader();
        foreach ($registry->paths($theme) as $namespace => $paths) {
            foreach ($paths as $path) {
                $loader->addPath($path, $namespace);
            }
        }

        return $loader;
    }
}
