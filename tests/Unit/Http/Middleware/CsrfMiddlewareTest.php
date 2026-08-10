<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Middleware;

use Gettext\Translator;
use Gettext\TranslatorFunctions;
use Johncms\Http\ExceptionResponseFactory;
use Johncms\Http\Middleware\CsrfMiddleware;
use Johncms\Http\Request;
use Johncms\Http\RequestPathNormalizer;
use Johncms\Http\Session;
use Johncms\Security\Csrf;
use Johncms\Security\CsrfExemptions;
use Johncms\View\RendererInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Tests for the CSRF check of the global pipeline.
 */
final class CsrfMiddlewareTest extends TestCase
{
    private const PASSED = 'the request reached the controller';

    private Csrf $csrf;

    protected function setUp(): void
    {
        // d__() is only declared once a translator is registered.
        TranslatorFunctions::register(new Translator());

        $this->csrf = new Csrf(new Session(new MockArraySessionStorage()));
    }

    public function testARequestCarryingTheTokenInTheBodyPasses(): void
    {
        $request = $this->postRequest(['csrf_token' => $this->csrf->getToken()]);

        self::assertSame(self::PASSED, $this->handle($request)->getContent());
    }

    /**
     * A JSON body has no form field to carry the token, so axios sends it as a header instead.
     */
    public function testARequestCarryingTheTokenInTheHeaderPasses(): void
    {
        $request = $this->postRequest();
        $request->headers->set(CsrfMiddleware::HEADER, $this->csrf->getToken());

        self::assertSame(self::PASSED, $this->handle($request)->getContent());
    }

    public function testARequestWithAnInvalidTokenIsRejected(): void
    {
        $response = $this->handle($this->postRequest(['csrf_token' => 'forged']));

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function testARequestWithoutATokenIsRejected(): void
    {
        $response = $this->handle($this->postRequest());

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function testASafeMethodIsNotChecked(): void
    {
        $request = Request::create('/guestbook', 'GET');

        self::assertSame(self::PASSED, $this->handle($request)->getContent());
    }

    public function testAnExemptedPathIsNotChecked(): void
    {
        $response = $this->handle(
            $this->postRequest(),
            exemptions: new CsrfExemptions(['/api/*'])
        );

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode(), 'the path is not in the list');

        $apiRequest = Request::create('/api/items', 'POST');

        self::assertSame(
            self::PASSED,
            $this->handle($apiRequest, exemptions: new CsrfExemptions(['/api/*']))->getContent()
        );
    }

    /**
     * The observation deployment: a failure is recorded but the request is served, so a form that
     * still misses the token surfaces in the log rather than in support.
     */
    public function testInObservationModeAFailureIsLoggedAndTheRequestIsServed(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('warning');

        $response = $this->handle($this->postRequest(), enforce: false, logger: $logger);

        self::assertSame(self::PASSED, $response->getContent());
    }

    public function testAFailureIsLoggedWhenTheCheckIsEnforced(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('warning');

        $this->handle($this->postRequest(), logger: $logger);
    }

    /**
     * @param array<string, mixed> $body
     */
    private function postRequest(array $body = []): Request
    {
        return Request::create('/guestbook', 'POST', $body);
    }

    private function handle(
        Request $request,
        bool $enforce = true,
        ?CsrfExemptions $exemptions = null,
        ?LoggerInterface $logger = null,
    ): Response {
        // The factory is final, so the real one is used with a renderer that returns a marker.
        $renderer = $this->createMock(RendererInterface::class);
        $renderer->method('render')->willReturn('rejected');

        $middleware = new CsrfMiddleware(
            $this->csrf,
            $exemptions ?? new CsrfExemptions(),
            new RequestPathNormalizer(),
            new ExceptionResponseFactory($renderer, new NullLogger()),
            $logger ?? new NullLogger(),
            $enforce,
        );

        return $middleware->handle($request, static fn (): Response => new Response(self::PASSED));
    }
}
