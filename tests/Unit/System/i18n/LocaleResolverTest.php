<?php

declare(strict_types=1);

namespace Tests\Unit\System\i18n;

use Johncms\Config\ConfigRepository;
use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\System\i18n\LocaleResolver;
use Johncms\System\Users\User;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

final class LocaleResolverTest extends TestCase
{
    private Session $session;

    protected function setUp(): void
    {
        ConfigRepository::init(
            [
                'johncms' => [
                    'lng'      => 'en',
                    'lng_list' => ['en' => 'English', 'ru' => 'Russian'],
                ],
            ]
        );

        $this->session = new Session(new MockArraySessionStorage());
    }

    public function testTheRequestedLocaleWinsAndIsRemembered(): void
    {
        $locale = $this->resolve('/?setlng=ru', $this->userWithLocale('en'));

        self::assertSame('ru', $locale);
        self::assertSame('ru', $this->session->get('lng'));
    }

    public function testAnUnknownRequestedLocaleIsIgnored(): void
    {
        self::assertSame('en', $this->resolve('/?setlng=klingon', new User()));
    }

    public function testTheLocaleOfTheSessionIsUsedWhenNothingIsRequested(): void
    {
        $this->session->set('lng', 'ru');

        self::assertSame('ru', $this->resolve('/', $this->userWithLocale('en')));
    }

    public function testTheLocaleOfTheProfileIsUsedWhenTheSessionHasNone(): void
    {
        $locale = $this->resolve('/', $this->userWithLocale('ru'));

        self::assertSame('ru', $locale);
        self::assertSame('ru', $this->session->get('lng'));
    }

    public function testTheSystemLocaleIsTheFallback(): void
    {
        self::assertSame('en', $this->resolve('/', new User()));
    }

    /**
     * The body is never read: this also runs during boot, where an unparsable JSON payload used
     * to kill the whole request with an uncaught JsonException.
     */
    public function testTheBodyIsNotRead(): void
    {
        $request = Request::create('/', 'POST', ['setlng' => 'ru']);
        $stack = new RequestStack();
        $stack->push($request);

        self::assertSame('en', (new LocaleResolver($stack, $this->session, new User()))->resolve());
    }

    public function testResolvingWithoutARequestFails(): void
    {
        $resolver = new LocaleResolver(new RequestStack(), $this->session, new User());

        $this->expectException(RuntimeException::class);
        $resolver->resolve();
    }

    private function resolve(string $uri, User $user): string
    {
        $stack = new RequestStack();
        $stack->push(Request::create($uri));

        return (new LocaleResolver($stack, $this->session, $user))->resolve();
    }

    private function userWithLocale(string $locale): User
    {
        return new User(['set_user' => serialize(['lng' => $locale])]);
    }
}
