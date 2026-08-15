<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Guestbook\Middlewares;

use Johncms\Config\ConfigRepository;
use Johncms\Exceptions\PageNotFoundException;
use Johncms\Http\Request;
use Johncms\Modules\Guestbook\Application\Middlewares\GuestbookCleanAccessMiddleware;
use Johncms\Modules\Guestbook\Application\Middlewares\GuestbookEditAccessMiddleware;
use Johncms\Modules\Guestbook\Application\Middlewares\GuestbookReplyAccessMiddleware;
use Johncms\Modules\Guestbook\Application\Services\GuestbookPermissions;
use Johncms\Router\MiddlewareInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Tests\Support\FakeAccessChecker;

final class GuestbookAccessMiddlewaresTest extends TestCase
{
    protected function setUp(): void
    {
        ConfigRepository::init([]);
    }

    /**
     * @param class-string $middlewareClass
     */
    #[DataProvider('middlewareProvider')]
    public function testPassesRequestWhenThePermissionIsHeld(string $middlewareClass, string $permission): void
    {
        $middleware = $this->makeMiddleware($middlewareClass, [$permission]);

        $result = $middleware->handle(Request::create('/'), static fn (): Response => new Response('passed'));

        self::assertSame('passed', $result->getContent());
    }

    /**
     * Each gate asks for its own permission: holding one of them is not holding the others.
     *
     * @param class-string $middlewareClass
     */
    #[DataProvider('middlewareProvider')]
    public function testThrowsWithoutThePermission(string $middlewareClass, string $permission): void
    {
        $others = array_values(array_diff(
            [GuestbookPermissions::CLEAR, GuestbookPermissions::ENTRY_MANAGE, GuestbookPermissions::ENTRY_REPLY],
            [$permission]
        ));

        $middleware = $this->makeMiddleware($middlewareClass, $others);

        $this->expectException(PageNotFoundException::class);
        $middleware->handle(Request::create('/'), static fn (): Response => new Response('passed'));
    }

    /**
     * @return array<string, array{class-string, string}>
     */
    public static function middlewareProvider(): array
    {
        return [
            'clean' => [GuestbookCleanAccessMiddleware::class, GuestbookPermissions::CLEAR],
            'edit'  => [GuestbookEditAccessMiddleware::class, GuestbookPermissions::ENTRY_MANAGE],
            'reply' => [GuestbookReplyAccessMiddleware::class, GuestbookPermissions::ENTRY_REPLY],
        ];
    }

    /**
     * @param class-string $middlewareClass
     * @param list<string> $granted
     */
    private function makeMiddleware(string $middlewareClass, array $granted): MiddlewareInterface
    {
        return new $middlewareClass(new FakeAccessChecker($granted));
    }
}
