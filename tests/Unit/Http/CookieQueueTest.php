<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use Johncms\Http\CookieQueue;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

final class CookieQueueTest extends TestCase
{
    public function testQueuedCookiesReachTheResponse(): void
    {
        $queue = new CookieQueue();
        $queue->add(Cookie::create('a', '1'));
        $queue->add(Cookie::create('b', '2'));

        $response = new Response();
        $queue->applyTo($response);

        $names = array_map(
            static fn (Cookie $cookie): string => $cookie->getName(),
            $response->headers->getCookies()
        );

        self::assertSame(['a', 'b'], $names);
    }

    /**
     * A queue that kept its contents would send the same cookie again on the next response of
     * the same process — in a worker runtime, to a different visitor.
     */
    public function testTheQueueIsEmptiedOnceApplied(): void
    {
        $queue = new CookieQueue();
        $queue->add(Cookie::create('a', '1'));
        $queue->applyTo(new Response());

        $second = new Response();
        $queue->applyTo($second);

        self::assertSame([], $second->headers->getCookies());
    }

    public function testResetDropsWhateverWasDecidedForTheServedRequest(): void
    {
        $queue = new CookieQueue();
        $queue->add(Cookie::create('a', '1'));

        $queue->reset();

        self::assertSame([], $queue->all());
    }
}
