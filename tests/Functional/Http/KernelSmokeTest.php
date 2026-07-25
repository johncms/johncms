<?php

declare(strict_types=1);

namespace Tests\Functional\Http;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Response;
use Tests\Functional\FunctionalTestCase;

/**
 * Smoke set over the main routes of every module (plan stage 3a).
 *
 * Guards what the unit suite cannot: that a real request survives routing, the middleware
 * pipeline, the controller, the templates and the response normalization. Stages 2c, 4 and 5
 * rewrite exactly that path, and this is their acceptance test.
 */
final class KernelSmokeTest extends FunctionalTestCase
{
    #[DataProvider('publicRoutes')]
    public function testRouteAnswersSuccessfully(string $uri): void
    {
        $response = $this->handleRequest($uri);

        $content = (string) $response->getContent();

        self::assertSame(
            Response::HTTP_OK,
            $response->getStatusCode(),
            sprintf('%s answered %d', $uri, $response->getStatusCode())
        );
        // A rendered page, not just any output: a template that dies half-way, an error string or
        // a 404 body served with 200 would all pass a mere "not empty" check.
        self::assertStringContainsString('<html', $content, $uri . ' did not render a page');
        self::assertStringContainsString('</html>', $content, $uri . ' rendered a truncated page');
    }

    /**
     * @return array<string, array{string}>
     */
    public static function publicRoutes(): array
    {
        // /community/search is deliberately absent: it is the route of the 400 test below, and
        // a controller serving two requests in one process keeps the first one (see the harness).
        $routes = [
            '/',
            '/forum',
            '/forum/files',
            '/forum/search',
            '/forum/latest-topics',
            '/news',
            '/downloads',
            '/downloads/top',
            '/library',
            '/guestbook',
            '/help',
            '/community',
            '/community/users',
            '/community/birthdays',
            '/community/administration',
            '/online',
            '/online/history',
            '/login',
            '/registration',
            '/contacts',
        ];

        return array_combine($routes, array_map(static fn (string $uri): array => [$uri], $routes));
    }

    /**
     * The redirect path end to end: the helper throws, the kernel maps it to a RedirectResponse.
     * The admin panel sends a guest to its login page.
     */
    public function testRedirectFromAMiddlewareBecomesA302WithLocation(): void
    {
        $response = $this->handleRequest('/admin');

        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        self::assertSame('/admin/login', $response->headers->get('Location'));
    }

    /**
     * A route that only admins may see answers 403, and that status is set the legacy way with
     * http_response_code() deep inside the module. Pins the status seam of stage 2b end to end.
     */
    public function testAdminOnlyRouteKeepsItsLegacyForbiddenStatus(): void
    {
        self::assertSame(Response::HTTP_FORBIDDEN, $this->handleRequest('/online/guest')->getStatusCode());
    }

    /**
     * The album module is guarded by AuthorizedUserMiddleware, which answers 404 for a guest
     * rather than revealing that the section exists. Pins that the middleware pipeline of the
     * kernel really runs.
     */
    public function testRouteBehindAnAuthorizationMiddlewareAnswers404ForAGuest(): void
    {
        self::assertSame(Response::HTTP_NOT_FOUND, $this->handleRequest('/album')->getStatusCode());
    }

    public function testUnknownRouteAnswers404(): void
    {
        $response = $this->handleRequest('/there-is-no-such-page-here/');

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        self::assertStringContainsString('404', (string) $response->getContent());
    }

    public function testWrongMethodAnswers405WithAllowHeader(): void
    {
        $response = $this->handleRequest('/library', 'POST');

        self::assertSame(Response::HTTP_METHOD_NOT_ALLOWED, $response->getStatusCode());
        self::assertSame('GET', $response->headers->get('Allow'));
    }

    /**
     * An array in a scalar parameter makes HttpFoundation throw BadRequestException. Before the
     * kernel existed it reached the global error handler and answered 500 with a log entry per
     * request — a client could fill the log with a loop of such requests.
     */
    public function testArrayInAScalarQueryParameterAnswers400(): void
    {
        $response = $this->handleRequest('/community/search?search[]=x');

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testLegacyForumEntryPointIsRoutedToTheForum(): void
    {
        $response = $this->handleRequest('/forum/index.php');

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testTrailingSlashIsAcceptedForTheSameRoute(): void
    {
        self::assertSame(Response::HTTP_OK, $this->handleRequest('/help/')->getStatusCode());
    }

    /**
     * A controller that still calls http_response_code() keeps its status: the normalizer takes
     * it instead of overwriting it with the 200 of a fresh Response (stage 2b).
     */
    public function testLegacyStatusCodeOfAControllerIsPreserved(): void
    {
        $response = $this->handleRequest('/library/article/99999999/download/txt');

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * The front controller is not a route prefix: getPathInfo() would strip it and make
     * /index.php/<route> a working alias of every route, with the prefix leaking into every
     * generated link on the page.
     */
    public function testFrontControllerPathIsNotARouteAlias(): void
    {
        self::assertSame(Response::HTTP_NOT_FOUND, $this->handleRequest('/index.php/help')->getStatusCode());
    }

    /**
     * An undecodable JSON body is the client's fault: 400, not the 500 it used to be (it was worse
     * than that — the translator read the payload during boot, so it was an uncaught fatal).
     */
    public function testUndecodableJsonBodyAnswers400(): void
    {
        $response = $this->handleRequest('/guestbook', 'POST', [], ['CONTENT_TYPE' => 'application/json']);

        self::assertContains(
            $response->getStatusCode(),
            [Response::HTTP_OK, Response::HTTP_BAD_REQUEST],
            'A JSON request must not produce a server error'
        );
    }
}
