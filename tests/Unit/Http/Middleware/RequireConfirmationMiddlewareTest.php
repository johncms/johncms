<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Middleware;

use Gettext\Translator;
use Gettext\TranslatorFunctions;
use Johncms\Http\ExceptionResponseFactory;
use Johncms\Http\Middleware\RequireConfirmationMiddleware;
use Johncms\Http\Request;
use Johncms\View\RendererInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests for the gate of the actions that are carried out only once the visitor confirmed them.
 */
final class RequireConfirmationMiddlewareTest extends TestCase
{
    private const string PASSED = 'the request reached the controller';

    protected function setUp(): void
    {
        // d__() is only declared once a translator is registered.
        TranslatorFunctions::register(new Translator());
    }

    public function testAConfirmedRequestPasses(): void
    {
        $response = $this->handle(Request::create('/guestbook/delpost', 'POST', ['confirmed' => '1']));

        self::assertSame(self::PASSED, $response->getContent());
    }

    /**
     * The script of the page did not run: the form came without the answer of the dialog.
     */
    public function testAnUnconfirmedRequestIsRefused(): void
    {
        $response = $this->handle(Request::create('/guestbook/delpost', 'POST', ['id' => '5']));

        self::assertSame(Response::HTTP_CONFLICT, $response->getStatusCode());
        self::assertSame('refused', $response->getContent());
    }

    public function testAConfirmationInTheQueryDoesNotCount(): void
    {
        $response = $this->handle(Request::create('/guestbook/delpost?confirmed=1', 'POST'));

        self::assertSame(Response::HTTP_CONFLICT, $response->getStatusCode());
    }

    public function testAnAjaxRequestIsRefusedWithJson(): void
    {
        $request = Request::create('/guestbook/delpost', 'POST', server: ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']);

        $response = $this->handle($request);

        self::assertSame(Response::HTTP_CONFLICT, $response->getStatusCode());
        self::assertArrayHasKey('message', json_decode((string) $response->getContent(), true));
    }

    public function testASafeMethodIsNotChecked(): void
    {
        $response = $this->handle(Request::create('/guestbook/delpost', 'GET'));

        self::assertSame(self::PASSED, $response->getContent());
    }

    private function handle(Request $request): Response
    {
        // The factory is final, so the real one is used with a renderer that returns a marker.
        $renderer = $this->createMock(RendererInterface::class);
        $renderer->method('render')->willReturn('refused');

        $middleware = new RequireConfirmationMiddleware(new ExceptionResponseFactory($renderer, new NullLogger()));

        return $middleware->handle($request, static fn (): Response => new Response(self::PASSED));
    }
}
