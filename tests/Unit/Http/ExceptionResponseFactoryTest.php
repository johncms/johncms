<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use Gettext\Translator;
use Gettext\TranslatorFunctions;
use Johncms\Exceptions\HttpRedirectException;
use Johncms\Exceptions\MethodNotAllowedException;
use Johncms\Exceptions\PageNotFoundException;
use Johncms\Http\ExceptionResponseFactory;
use Johncms\System\View\Render;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests for the factory that turns control-flow exceptions into responses (plan stage 2a).
 */
final class ExceptionResponseFactoryTest extends TestCase
{
    protected function setUp(): void
    {
        // d__() is only declared once a translator is registered.
        TranslatorFunctions::register(new Translator());
    }

    public function testRedirectExceptionBecomesARedirectResponse(): void
    {
        $factory = new ExceptionResponseFactory($this->createMock(Render::class));

        $response = $factory->fromRedirect(new HttpRedirectException('/forum/', 301));

        self::assertSame(301, $response->getStatusCode());
        self::assertSame('/forum/', $response->getTargetUrl());
        self::assertSame('/forum/', $response->headers->get('Location'));
    }

    public function testMethodNotAllowedExceptionBecomesA405WithAnAllowHeader(): void
    {
        $factory = new ExceptionResponseFactory($this->createMock(Render::class));

        $response = $factory->fromMethodNotAllowed(new MethodNotAllowedException(['GET', 'HEAD']));

        self::assertSame(Response::HTTP_METHOD_NOT_ALLOWED, $response->getStatusCode());
        self::assertSame('GET, HEAD', $response->headers->get('Allow'));
        self::assertSame('text/plain; charset=UTF-8', $response->headers->get('Content-Type'));
        self::assertSame('Method Not Allowed', $response->getContent());
    }

    public function testMethodNotAllowedWithoutKnownMethodsSendsNoAllowHeader(): void
    {
        $factory = new ExceptionResponseFactory($this->createMock(Render::class));

        $response = $factory->fromMethodNotAllowed(new MethodNotAllowedException());

        self::assertSame(Response::HTTP_METHOD_NOT_ALLOWED, $response->getStatusCode());
        self::assertFalse($response->headers->has('Allow'));
    }

    public function testBadRequestResponse(): void
    {
        $factory = new ExceptionResponseFactory($this->createMock(Render::class));

        $response = $factory->badRequest();

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame('text/plain; charset=UTF-8', $response->headers->get('Content-Type'));
    }

    public function testInternalServerErrorHidesTheDetailsUnlessTheyAreAllowed(): void
    {
        $factory = new ExceptionResponseFactory($this->createMock(Render::class));

        $response = $factory->internalServerError(new RuntimeException('secret failure'), false);

        self::assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        self::assertSame('Internal Server Error', $response->getContent());
        self::assertStringNotContainsString('secret failure', (string) $response->getContent());
    }

    public function testInternalServerErrorEscapesTheDetailsWhenTheyAreAllowed(): void
    {
        $factory = new ExceptionResponseFactory($this->createMock(Render::class));

        $response = $factory->internalServerError(new RuntimeException('<script>alert(1)</script>'), true);

        $content = (string) $response->getContent();
        self::assertStringContainsString('&lt;script&gt;', $content);
        self::assertStringNotContainsString('<script>', $content);
    }

    public function testPageNotFoundExceptionIsRenderedWithStatus404(): void
    {
        $render = $this->createMock(Render::class);
        $render->expects(self::once())
            ->method('render')
            ->with('system::error/404', ['title' => 'Custom title', 'message' => 'Custom message'])
            ->willReturn('rendered 404');

        $factory = new ExceptionResponseFactory($render);

        $response = $factory->fromPageNotFound(
            (new PageNotFoundException('Custom message'))->setTitle('Custom title')
        );

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        self::assertSame('rendered 404', $response->getContent());
    }

    public function testEmptyTitleAndMessageAreReplacedWithDefaults(): void
    {
        $render = $this->createMock(Render::class);
        $render->expects(self::once())
            ->method('render')
            ->with(
                'system::error/404',
                [
                    'title'   => 'ERROR: 404 Not Found',
                    'message' => 'You are looking for something that doesn\'t exist or may have moved',
                ]
            )
            ->willReturn('rendered 404');

        $factory = new ExceptionResponseFactory($render);

        $response = $factory->fromPageNotFound(new PageNotFoundException());

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    public function testCustomTemplateIsUsed(): void
    {
        $render = $this->createMock(Render::class);
        $render->expects(self::once())
            ->method('render')
            ->with('system::pages/result', self::anything())
            ->willReturn('rendered page');

        $factory = new ExceptionResponseFactory($render);

        $response = $factory->fromPageNotFound(
            (new PageNotFoundException())->setTemplate('system::pages/result')
        );

        self::assertSame('rendered page', $response->getContent());
    }
}
