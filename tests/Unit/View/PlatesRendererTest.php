<?php

declare(strict_types=1);

namespace Tests\Unit\View;

use Johncms\System\View\Render;
use Johncms\View\PlatesRenderer;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class PlatesRendererTest extends TestCase
{
    public function testItRendersThroughTheEngine(): void
    {
        self::assertSame(
            "Hello, World!\n",
            $this->renderer()->render('tests::hello', ['name' => 'World'])
        );
    }

    /**
     * The adapter adds no error handling of its own: a failing template reaches the kernel, which
     * logs it and answers 500, instead of becoming the body of a 200.
     */
    public function testAFailingTemplatePropagates(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('template exploded');

        $this->renderer()->render('tests::boom');
    }

    public function testExistsAnswersForATemplateThatIsThere(): void
    {
        self::assertTrue($this->renderer()->exists('tests::hello'));
    }

    public function testExistsIsFalseForAMissingFileAndForAnUnknownNamespace(): void
    {
        self::assertFalse($this->renderer()->exists('tests::there-is-no-such-template'));
        self::assertFalse($this->renderer()->exists('nosuchnamespace::hello'));
    }

    private function renderer(): PlatesRenderer
    {
        $engine = new Render();
        $engine->addFolder('tests', __DIR__ . '/../System/View/templates');

        return new PlatesRenderer($engine);
    }
}
