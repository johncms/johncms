<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Guestbook\Middlewares;

use Johncms\Config\ConfigRepository;
use Johncms\Exceptions\PageNotFoundException;
use Johncms\Modules\Guestbook\Application\Middlewares\GuestbookCleanAccessMiddleware;
use Johncms\Modules\Guestbook\Application\Middlewares\GuestbookEditAccessMiddleware;
use Johncms\Modules\Guestbook\Application\Middlewares\GuestbookReplyAccessMiddleware;
use Johncms\Router\MiddlewareInterface;
use Johncms\System\Http\Request;
use Johncms\System\Users\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class GuestbookAccessMiddlewaresTest extends TestCase
{
    protected function setUp(): void
    {
        ConfigRepository::init([]);
    }

    /**
     * @param class-string $middlewareClass
     */
    #[DataProvider('allowedProvider')]
    public function testPassesRequestWhenRightsAreSufficient(string $middlewareClass, int $rights): void
    {
        $middleware = $this->makeMiddleware($middlewareClass, $this->makeUser($rights));

        $result = $middleware->handle($this->createMock(Request::class), static fn () => 'passed');

        self::assertSame('passed', $result);
    }

    public static function allowedProvider(): array
    {
        return [
            'clean: rights 7' => [GuestbookCleanAccessMiddleware::class, 7],
            'edit: rights 1'  => [GuestbookEditAccessMiddleware::class, 1],
            'reply: rights 6' => [GuestbookReplyAccessMiddleware::class, 6],
        ];
    }

    /**
     * @param class-string $middlewareClass
     */
    #[DataProvider('deniedProvider')]
    public function testThrowsWhenRightsAreInsufficient(string $middlewareClass, int $rights): void
    {
        $middleware = $this->makeMiddleware($middlewareClass, $this->makeUser($rights));

        $this->expectException(PageNotFoundException::class);
        $middleware->handle($this->createMock(Request::class), static fn () => 'passed');
    }

    public static function deniedProvider(): array
    {
        return [
            'clean: rights 6' => [GuestbookCleanAccessMiddleware::class, 6],
            'edit: rights 0'  => [GuestbookEditAccessMiddleware::class, 0],
            'reply: rights 5' => [GuestbookReplyAccessMiddleware::class, 5],
        ];
    }

    /**
     * @param class-string $middlewareClass
     */
    #[DataProvider('middlewareProvider')]
    public function testThrowsForInvalidUser(string $middlewareClass): void
    {
        $guest = new User(['id' => 0, 'preg' => 0, 'rights' => 9]);
        $middleware = $this->makeMiddleware($middlewareClass, $guest);

        $this->expectException(PageNotFoundException::class);
        $middleware->handle($this->createMock(Request::class), static fn () => 'passed');
    }

    public static function middlewareProvider(): array
    {
        return [
            'clean' => [GuestbookCleanAccessMiddleware::class],
            'edit'  => [GuestbookEditAccessMiddleware::class],
            'reply' => [GuestbookReplyAccessMiddleware::class],
        ];
    }

    private function makeUser(int $rights): User
    {
        return new User(['id' => 1, 'preg' => 1, 'rights' => $rights]);
    }

    /**
     * @param class-string $middlewareClass
     */
    private function makeMiddleware(string $middlewareClass, User $user): MiddlewareInterface
    {
        return new $middlewareClass($user);
    }
}
