<?php

declare(strict_types=1);

namespace Tests\Unit\Router;

use GuzzleHttp\Psr7\Uri;
use Johncms\Router\RequestContextFactory;
use Johncms\System\Http\Request;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UriInterface;

final class RequestContextFactoryTest extends TestCase
{
    public function testInvokeBuildsContextFromRequestData(): void
    {
        $request = $this->createMock(Request::class);
        $request->method('getMethod')->willReturn('POST');
        $request->method('getUri')->willReturn(new Uri('https://domain.com/forum/topic/15?page=2'));
        $request->method('isHttps')->willReturn(true);

        $factory = new RequestContextFactory($request);
        $context = $factory();

        self::assertSame('POST', $context->getMethod());
        self::assertSame('domain.com', $context->getHost());
        self::assertSame('https', $context->getScheme());
        self::assertSame('/forum/topic/15', $context->getPathInfo());
        self::assertSame('page=2', $context->getQueryString());
    }

    public function testInvokeAppliesHostAndPathFallbacks(): void
    {
        $uri = $this->createMock(UriInterface::class);
        $uri->method('getHost')->willReturn('');
        $uri->method('getPath')->willReturn('');
        $uri->method('getQuery')->willReturn('');

        $request = $this->createMock(Request::class);
        $request->method('getMethod')->willReturn('GET');
        $request->method('getUri')->willReturn($uri);
        $request->method('isHttps')->willReturn(false);

        $factory = new RequestContextFactory($request);
        $context = $factory();

        self::assertSame('localhost', $context->getHost());
        self::assertSame('/', $context->getPathInfo());
        self::assertSame('http', $context->getScheme());
    }
}
