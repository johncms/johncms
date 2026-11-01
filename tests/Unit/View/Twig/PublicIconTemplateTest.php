<?php

declare(strict_types=1);

namespace Tests\Unit\View\Twig;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;

final class PublicIconTemplateTest extends TestCase
{
    public function testKnownNamesAndLegacyAliasesResolveToShippedSymbols(): void
    {
        $definition = json_decode(
            file_get_contents(THEMES_PATH . 'default/src/icons/icons.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
        $sprite = file_get_contents(ROOT_PATH . 'public/themes/default/assets/icons/public.svg');

        foreach ($definition['icons'] as $name) {
            $html = $this->render(['name' => $name]);

            self::assertStringContainsString('/icons/public.svg#' . $name . '"', $html);
            self::assertStringContainsString('site-icon--lucide', $html);
            self::assertStringContainsString('<symbol id="' . $name . '"', $sprite);
            self::assertStringContainsString('aria-hidden="true"', $html);
        }

        foreach ($definition['aliases'] as $alias => $name) {
            self::assertStringContainsString('/icons/public.svg#' . $name . '"', $this->render(['name' => $alias]));
        }
    }

    public function testAnUnknownNameKeepsTheLegacySpriteAndEscapesAttributes(): void
    {
        $html = $this->render(['name' => 'custom" onload="alert(1)', 'class' => 'custom" onclick="alert(2)']);

        self::assertStringContainsString('site-icon--legacy', $html);
        self::assertStringContainsString('/icons/sprite.svg#custom&quot; onload=&quot;alert(1)', $html);
        self::assertStringNotContainsString(' onload="', $html);
        self::assertStringNotContainsString(' onclick="', $html);
    }

    public function testSocialIconsKeepTheLegacySprite(): void
    {
        self::assertStringContainsString('/icons/sprite.svg#telegram"', $this->render(['name' => 'telegram']));
    }

    public function testAnImageKeepsItsUrlAndAccessibleLabel(): void
    {
        $html = $this->render([
            'name' => '/module/icon.svg?x=1&y=2',
            'image' => true,
            'label' => 'A "custom" icon',
            'size' => 24,
        ]);

        self::assertStringContainsString('<img ', $html);
        self::assertStringContainsString('src="/module/icon.svg?x=1&amp;y=2"', $html);
        self::assertStringContainsString('alt="A &quot;custom&quot; icon"', $html);
        self::assertStringContainsString('width="24" height="24"', $html);
        self::assertStringNotContainsString('aria-hidden', $html);
        self::assertStringNotContainsString('<svg', $html);
    }

    public function testALabelledSvgIsExposedToAssistiveTechnology(): void
    {
        $html = $this->render(['name' => 'menu', 'label' => 'Open "menu"']);

        self::assertStringContainsString('role="img" aria-label="Open &quot;menu&quot;"', $html);
        self::assertStringNotContainsString('aria-hidden', $html);
    }

    public function testAnEmptyNameDrawsNothing(): void
    {
        self::assertSame('', trim($this->render(['name' => null])));
        self::assertSame('', trim($this->render([])));
    }

    /**
     * @param array<string, mixed> $data
     */
    private function render(array $data): string
    {
        $loader = new FilesystemLoader();
        $loader->addPath(THEMES_PATH . 'default/templates', 'theme');
        $twig = new Environment($loader, ['autoescape' => 'html', 'strict_variables' => true]);
        $twig->addFunction(new TwigFunction('asset', static fn(string $path, bool $absolute = false): string => '/theme/' . $path));

        return $twig->render('@theme/components/icon.twig', $data);
    }
}
