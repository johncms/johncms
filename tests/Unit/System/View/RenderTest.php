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
 * caller. A template failure is caught by Kernel::handle(), which logs it and
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
     * to raise and take the request down.
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

    /**
     * The engine is a container singleton, so the theme cannot be baked into it: it depends on
     * the request being served, and under a long-running runtime the first request would decide
     * it for every later one. The resolver is asked when a namespace is resolved, which happens
     * while a template is being rendered.
     */
    public function testTheThemeResolverIsCalledWhenANamespaceIsResolvedAndNotWhenItIsRegistered(): void
    {
        $calls = 0;
        $render = new Render();
        $render->setThemeResolver(function () use (&$calls): string {
            $calls++;

            return 'default';
        });

        $render->addFolder('tests', __DIR__ . '/templates');
        self::assertSame(0, $calls);

        $render->getFolder('tests');
        self::assertSame(1, $calls);
    }

    /**
     * The fallback chain of the shipped example theme: it holds a single template overriding the
     * homepage module, and everything else falls back. Plates walks the list backwards, so the
     * theme path has to be the last entry.
     */
    public function testThePathOfTheCurrentThemeIsSearchedBeforeTheModule(): void
    {
        $render = new Render();
        $render->setThemeResolver(static fn (): string => 'example');
        $render->addFolder('homepage', MODULES_PATH . 'homepage/templates');

        self::assertSame(
            [
                rtrim(MODULES_PATH . 'homepage/templates', DS),
                realpath(THEMES_PATH . 'example/templates/homepage'),
            ],
            $render->getFolder('homepage')
        );
    }

    public function testANamespaceTheThemeDoesNotOverrideResolvesToTheModuleOnly(): void
    {
        $render = new Render();
        $render->setThemeResolver(static fn (): string => 'example');
        $render->addFolder('tests', __DIR__ . '/templates');

        self::assertSame([__DIR__ . '/templates'], $render->getFolder('tests'));
    }

    private function engine(): Render
    {
        $render = new Render();
        $render->addFolder('tests', __DIR__ . '/templates');

        return $render;
    }
}
