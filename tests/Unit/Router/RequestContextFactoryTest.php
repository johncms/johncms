<?php

declare(strict_types=1);

namespace Tests\Unit\Router;

use Johncms\Router\RequestContextFactory;
use PHPUnit\Framework\TestCase;

/**
 * The factory builds an empty context on purpose (plan stage 3a).
 *
 * It used to fill it from the request, which resolved the host while the container was still
 * building the kernel: a Host header failing the trusted-host patterns then raised
 * SuspiciousOperationException outside Kernel::handle() and answered 500 with a log entry per
 * request. SymfonyRouteMatcher::matchRequest() now fills the context from the request it matches
 * — see SymfonyRouteMatcherTest::testMatchRequestNormalizesThePathAndRefreshesTheContext.
 */
final class RequestContextFactoryTest extends TestCase
{
    public function testInvokeBuildsAnEmptyContextWithoutTouchingTheRequest(): void
    {
        $context = (new RequestContextFactory())();

        self::assertSame('localhost', $context->getHost());
        self::assertSame('/', $context->getPathInfo());
        self::assertSame('http', $context->getScheme());
        self::assertSame('GET', $context->getMethod());
        self::assertSame('', $context->getQueryString());
    }
}
