<?php

declare(strict_types=1);

namespace Tests\Unit\View\Twig;

use Johncms\System\View\Render;
use Johncms\View\PlatesRenderer;
use Johncms\View\Twig\Extension\PlatesBridgeExtension;
use Johncms\View\Twig\Runtime\PlatesRuntime;
use Johncms\View\Twig\TwigRenderer;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\RuntimeLoader\FactoryRuntimeLoader;

/**
 * What the environment guarantees to every template: output is escaped unless it is declared
 * markup, and a page can still pull in a partial that has not moved off Plates yet.
 */
final class TwigEnvironmentTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . DS . 'johncms-twig-' . uniqid() . DS;
        mkdir($this->root);
    }

    protected function tearDown(): void
    {
        exec('rm -rf ' . escapeshellarg($this->root));
    }

    public function testOutputIsEscapedByDefault(): void
    {
        $this->writeTemplate('escaping.twig', '{{ text }}');

        self::assertSame(
            '&lt;b&gt;bold&lt;/b&gt;',
            $this->renderer()->render('@tests/escaping.twig', ['text' => '<b>bold</b>'])
        );
    }

    public function testTheRawFilterStillPrintsMarkup(): void
    {
        $this->writeTemplate('raw.twig', '{{ text|raw }}');

        self::assertSame(
            '<b>bold</b>',
            $this->renderer()->render('@tests/raw.twig', ['text' => '<b>bold</b>'])
        );
    }

    /**
     * A Plates partial returns finished markup. Escaping it would print its tags, so the bridge
     * hands it over as Markup and the template needs no raw filter.
     */
    public function testAPlatesPartialIsIncludedAsMarkup(): void
    {
        $this->writeTemplate('bridge.twig', "{{ plates('tests::hello', {name: 'World'}) }}");

        self::assertSame(
            "Hello, World!\n",
            $this->renderer()->render('@tests/bridge.twig')
        );
    }

    public function testAMissingTemplateIsReportedByExists(): void
    {
        $this->writeTemplate('present.twig', 'here');
        $renderer = $this->renderer();

        self::assertTrue($renderer->exists('@tests/present.twig'));
        self::assertFalse($renderer->exists('@tests/absent.twig'));
    }

    private function renderer(): TwigRenderer
    {
        $loader = new FilesystemLoader();
        $loader->addPath($this->root, 'tests');

        $twig = new Environment($loader, ['autoescape' => 'html', 'cache' => false, 'strict_variables' => true]);
        $twig->addExtension(new PlatesBridgeExtension());

        $plates = new Render();
        $plates->addFolder('tests', __DIR__ . '/../../System/View/templates');
        $twig->addRuntimeLoader(new FactoryRuntimeLoader([
            PlatesRuntime::class => static fn (): PlatesRuntime => new PlatesRuntime(new PlatesRenderer($plates)),
        ]));

        return new TwigRenderer($twig);
    }

    private function writeTemplate(string $name, string $contents): void
    {
        file_put_contents($this->root . $name, $contents);
    }
}
