<?php

declare(strict_types=1);

namespace Tests\Unit\System\View;

use InvalidArgumentException;
use Johncms\System\View\Render;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Tests for the view engine.
 *
 * Render used to override render() purely to catch every Throwable and return the exception
 * message as the rendered output. That turned any template failure into a 200 response whose
 * body was a raw error string, leaked the message to visitors regardless of DEBUG, and logged
 * nothing. Templates execute inside that call, so it also hid anything they raise from the
 * caller. Since stage 3a a template failure is caught by Kernel::handle(), which logs it and
 * answers 500; the point of these tests is that the view layer does not swallow it first.
 */
final class RenderTest extends TestCase
{
    public function testRendersATemplate(): void
    {
        self::assertSame("Hello, World!\n", $this->engine()->render('tests::hello', ['name' => 'World']));
    }

    public function testAFailureInsideANestedTemplatePropagatesToo(): void
    {
        // The layout path matters on its own: a page template that renders fine can still pull
        // in a failing partial, and that used to collapse the whole response to a message.
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('template exploded');

        $this->engine()->render('tests::nested');
    }

    public function testAFailingTemplatePropagatesInsteadOfBecomingThePageBody(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('template exploded');

        $this->engine()->render('tests::boom');
    }

    /**
     * The engine is a container singleton while every controller registers its module namespace on
     * construction, so a second request in one process re-registers the same namespace. That used
     * to raise and take the request down (plan stage 3a).
     */
    public function testRegisteringTheSameFolderTwiceIsANoOp(): void
    {
        $render = $this->engine();
        $render->addFolder('tests', __DIR__ . '/templates');

        self::assertSame("Hello, World!\n", $render->render('tests::hello', ['name' => 'World']));
    }

    public function testADifferentDirectoryUnderATakenNamespaceStillRaises(): void
    {
        $render = $this->engine();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('already being used');

        $render->addFolder('tests', __DIR__);
    }

    private function engine(): Render
    {
        $render = new Render();
        $render->addFolder('tests', __DIR__ . '/templates');

        return $render;
    }
}
