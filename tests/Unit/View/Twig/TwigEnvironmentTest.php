<?php

declare(strict_types=1);

namespace Tests\Unit\View\Twig;

use Johncms\View\Twig\TwigRenderer;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/**
 * What the environment guarantees to every template: output is escaped unless it is declared
 * markup.
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

        return new TwigRenderer($twig);
    }

    private function writeTemplate(string $name, string $contents): void
    {
        file_put_contents($this->root . $name, $contents);
    }
}
