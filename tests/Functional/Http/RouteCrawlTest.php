<?php

declare(strict_types=1);

namespace Tests\Functional\Http;

use Dom\HTMLDocument;
use Johncms\Router\Route;
use Johncms\Router\RouteCollectorFactory;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\RequestContext;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\FunctionalDatabase;
use Tests\Support\FunctionalUserFactory;
use Throwable;

/**
 * Every page the router can address without being told what to show, opened by a guest, by an
 * ordinary member and by the staff.
 *
 * The list is read from the router, so a page is covered the moment it is declared and nobody has
 * to remember to add it here. A route whose path has a placeholder without a default is left out:
 * what to fill it with is a fixture, and that belongs to a test about the page.
 *
 * What holds for every page, whoever opens it:
 *
 * - it answers without a server error;
 * - a document it starts, it finishes — a template that dies half-way would pass as a 200;
 * - every form that posts carries the CSRF token, which the check refuses a form without — and a
 *   form missing one shows up only when somebody presses its button;
 * - the panel lets nobody in but the staff, and the staff is refused nothing anywhere.
 */
final class RouteCrawlTest extends FunctionalTestCase
{
    private const string GUEST = 'guest';
    private const string MEMBER = 'member';
    private const string STAFF = 'staff';

    /**
     * Pages that ask MySQL itself for something, which SQLite cannot answer, by route name and
     * the visitor who gets that far. Against MySQL they are opened like the rest.
     */
    private const array MYSQL_ONLY = [
        // Reads the version of the server with SELECT VERSION().
        'admin.system_check' => self::STAFF,
    ];

    /** The one page of the panel that is there for a visitor who is not signed in yet. */
    private const string PANEL_SIGN_IN = 'admin.login';

    #[DataProvider('visits')]
    public function testThePageAnswers(string $route, string $path, string $visitor, bool $panel): void
    {
        $cookies = $this->cookiesOf($visitor);
        $response = $this->handleRequest($path, cookies: $cookies);
        $status = $response->getStatusCode();

        self::assertLessThan(
            Response::HTTP_INTERNAL_SERVER_ERROR,
            $status,
            $status < Response::HTTP_INTERNAL_SERVER_ERROR
                ? ''
                : sprintf('%s answered %d to the %s. %s', $path, $status, $visitor, $this->explainServerError($path, $cookies))
        );

        if ($visitor === self::STAFF) {
            self::assertNotSame(Response::HTTP_FORBIDDEN, $status, sprintf('%s refused the staff.', $path));
        } elseif ($panel && $route !== self::PANEL_SIGN_IN) {
            self::assertFalse($response->isSuccessful(), sprintf('The panel let the %s into %s.', $visitor, $path));
        }

        if (! str_starts_with((string) $response->headers->get('Content-Type'), 'text/html')) {
            return;
        }

        $content = (string) $response->getContent();

        if (str_contains($content, '<html')) {
            self::assertStringContainsString(
                '</html>',
                $content,
                sprintf('%s rendered a truncated page to the %s.', $path, $visitor)
            );
        }

        self::assertSame(
            [],
            $this->formsPostingWithoutAToken($content),
            sprintf('%s shows the %s forms that post without the CSRF token, listed by action.', $path, $visitor)
        );
    }

    /**
     * @return iterable<string, array{string, string, string, bool}>
     */
    public static function visits(): iterable
    {
        // A data provider runs before the application is booted, and building the routes needs
        // nothing of it: the collection asks the container for nothing (RouteCollectorFactoryTest).
        $routes = (new RouteCollectorFactory())(self::noContainer());
        $urls = new UrlGenerator($routes, new RequestContext());
        $onMysql = FunctionalDatabase::fromEnvironment()->isMysql();

        foreach ($routes->all() as $name => $route) {
            if ($route->getMethods() !== [] && ! in_array('GET', $route->getMethods(), true)) {
                continue;
            }

            try {
                $path = $urls->generate($name);
            } catch (MissingMandatoryParametersException) {
                continue;
            }

            $panel = $route->getDefault(Route::ADMIN_AREA_ATTRIBUTE) === true;

            foreach ([self::GUEST, self::MEMBER, self::STAFF] as $visitor) {
                if (! $onMysql && (self::MYSQL_ONLY[$name] ?? null) === $visitor) {
                    continue;
                }

                yield $name . ' as ' . $visitor => [$name, $path, $visitor, $panel];
            }
        }
    }

    /**
     * @return array<string, string>
     */
    private function cookiesOf(string $visitor): array
    {
        return match ($visitor) {
            self::GUEST  => [],
            self::MEMBER => $this->actingAs(FunctionalUserFactory::create()),
            self::STAFF  => $this->actingAs(FunctionalUserFactory::createSupervisor()),
            default      => throw new LogicException(sprintf('There is no visitor called "%s".', $visitor)),
        };
    }

    /**
     * The kernel turns an exception into a 500 and writes it to the log, which CI does not show.
     * Asked again without catching, the request hands the exception over.
     *
     * @param array<string, string> $cookies
     */
    private function explainServerError(string $path, array $cookies): string
    {
        try {
            $this->handleRequest($path, cookies: $cookies, catch: false);
        } catch (Throwable $exception) {
            return sprintf(
                '%s: %s at %s:%d',
                $exception::class,
                $exception->getMessage(),
                $exception->getFile(),
                $exception->getLine()
            );
        }

        return 'Asked again, it did not fail.';
    }

    /**
     * @return list<string>
     */
    private function formsPostingWithoutAToken(string $html): array
    {
        if (! str_contains($html, '<form')) {
            return [];
        }

        $forms = [];

        foreach (HTMLDocument::createFromString($html, LIBXML_NOERROR)->querySelectorAll('form') as $form) {
            if (strtolower((string) $form->getAttribute('method')) !== 'post') {
                continue;
            }

            if ($form->querySelector('input[name="csrf_token"]') === null) {
                $forms[] = (string) $form->getAttribute('action');
            }
        }

        return $forms;
    }

    private static function noContainer(): ContainerInterface
    {
        return new class implements ContainerInterface {
            public function get(string $id): mixed
            {
                throw new LogicException(sprintf('Building the routes asked the container for %s.', $id));
            }

            public function has(string $id): bool
            {
                return false;
            }
        };
    }
}
