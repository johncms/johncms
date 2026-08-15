<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Middleware;

use Gettext\Translator;
use Gettext\TranslatorFunctions;
use Johncms\Auth\Authentication\AuthenticatorChain;
use Johncms\Auth\Authorization\PermissionResolver;
use Johncms\Auth\CurrentUser;
use Johncms\Auth\Identity;
use Johncms\Exceptions\HttpRedirectException;
use Johncms\Exceptions\PageNotFoundException;
use Johncms\Http\ExceptionResponseFactory;
use Johncms\Http\Middleware\RequirePermissionMiddleware;
use Johncms\Http\Request;
use Johncms\Router\Route;
use Johncms\View\RendererInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Tests\Support\FakeAccessChecker;
use Tests\Support\FakeAuthenticator;
use Tests\Support\FakeRoleRepository;
use Tests\Support\IdentityFactory;

/**
 * The gate of a route that named a permission.
 */
final class RequirePermissionMiddlewareTest extends TestCase
{
    private const PASSED = 'the request reached the controller';

    protected function setUp(): void
    {
        // d__() is only declared once a translator is registered.
        TranslatorFunctions::register(new Translator());
    }

    public function testARouteWithoutAPermissionIsNotGuarded(): void
    {
        $response = $this->handle($this->request(), granted: []);

        self::assertSame(self::PASSED, $response->getContent());
    }

    public function testTheVisitorHoldingThePermissionPasses(): void
    {
        $response = $this->handle($this->request('news.manage'), granted: ['news.manage']);

        self::assertSame(self::PASSED, $response->getContent());
    }

    public function testAVisitorWithoutThePermissionIsRefused(): void
    {
        $response = $this->handle($this->request('news.manage'), granted: ['news.comments.post']);

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    /**
     * The components of the theme read response.data.message, so an XHR is answered in JSON.
     */
    public function testARefusedXhrIsAnsweredInJson(): void
    {
        $request = $this->request('news.manage');
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');

        $response = $this->handle($request, granted: []);

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        self::assertStringContainsString('application/json', (string) $response->headers->get('Content-Type'));
    }

    /**
     * For a guest the refusal is usually a missing session rather than a missing right.
     */
    public function testAGuestIsSentToSignIn(): void
    {
        $this->expectException(HttpRedirectException::class);

        $this->handle($this->request('news.manage'), granted: [], identity: Identity::guest());
    }

    /**
     * The rare route whose very existence is the secret answers as if it were not there.
     */
    public function testAHiddenRouteAnswersAsIfItDidNotExist(): void
    {
        $this->expectException(PageNotFoundException::class);

        $this->handle($this->request('secret.view', hidden: true), granted: []);
    }

    private function request(?string $permission = null, bool $hidden = false): Request
    {
        $request = Request::create('/admin/news', 'GET');

        if ($permission !== null) {
            $request->attributes->set(Route::PERMISSION_ATTRIBUTE, $permission);
            $request->attributes->set(Route::PERMISSION_HIDDEN_ATTRIBUTE, $hidden);
        }

        return $request;
    }

    /**
     * @param list<string> $granted
     */
    private function handle(Request $request, array $granted, ?Identity $identity = null): Response
    {
        $renderer = $this->createMock(RendererInterface::class);
        $renderer->method('render')->willReturn('refused');

        $stack = new RequestStack();
        $stack->push($request);

        $currentUser = new CurrentUser(
            new AuthenticatorChain([new FakeAuthenticator($identity ?? IdentityFactory::user())]),
            new PermissionResolver(new FakeRoleRepository()),
            $stack
        );

        $middleware = new RequirePermissionMiddleware(
            $currentUser,
            new FakeAccessChecker($granted),
            new ExceptionResponseFactory($renderer, new NullLogger()),
        );

        return $middleware->handle($request, static fn (): Response => new Response(self::PASSED));
    }
}
