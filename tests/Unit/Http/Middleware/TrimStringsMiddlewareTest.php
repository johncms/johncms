<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Middleware;

use Johncms\Http\Middleware\TrimStringsMiddleware;
use Johncms\Http\Request;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests for TrimStringsMiddleware (plan stage 1a-bis): reproduces the legacy always-trim
 * behaviour that moved out of the Request wrapper into one explicit middleware.
 */
final class TrimStringsMiddlewareTest extends TestCase
{
    private function makeRequest(array $query = [], array $post = []): Request
    {
        return new Request($query, $post);
    }

    public function testTrimsBodyValues(): void
    {
        $request = $this->makeRequest(post: ['name' => '  John  ', 'about' => "\ttext\n"]);

        $this->applyMiddleware($request);

        self::assertSame('John', $request->request->all()['name']);
        self::assertSame('text', $request->request->all()['about']);
    }

    public function testTrimsQueryValues(): void
    {
        $request = $this->makeRequest(query: ['q' => '  search  ']);

        $this->applyMiddleware($request);

        self::assertSame('search', $request->query->all()['q']);
    }

    public function testTrimsNestedArraysRecursively(): void
    {
        $request = $this->makeRequest(post: ['users' => ['  a ', ' b ', ['  c  ']]]);

        $this->applyMiddleware($request);

        self::assertSame(['a', 'b', ['c']], $request->request->all()['users']);
    }

    public function testWhitespaceOnlyValueBecomesEmptyString(): void
    {
        $request = $this->makeRequest(post: ['field' => '    ']);

        $this->applyMiddleware($request);

        self::assertSame('', $request->request->all()['field']);
    }

    public function testPasswordsAreTrimmedToo(): void
    {
        // The exception list is empty by design: the historical behaviour trimmed passwords.
        $request = $this->makeRequest(post: ['password' => '  secret  ']);

        $this->applyMiddleware($request);

        self::assertSame('secret', $request->request->all()['password']);
    }

    public function testWrapperHelpersSeeTrimmedValues(): void
    {
        $request = $this->makeRequest(post: ['message' => '  hi  '], query: ['page' => ' 3 ']);

        $this->applyMiddleware($request);

        self::assertSame('hi', $request->body('message'));
        self::assertSame(3, $request->queryInt('page'));
    }

    public function testCallsNextWithTheRequestAndReturnsItsResult(): void
    {
        $request = $this->makeRequest(post: ['a' => ' x ']);
        $received = null;

        $result = (new TrimStringsMiddleware())->handle(
            $request,
            static function (Request $passed) use (&$received): Response {
                $received = $passed;
                return new Response('response');
            },
        );

        self::assertSame($request, $received);
        self::assertSame('response', $result->getContent());
    }

    private function applyMiddleware(Request $request): void
    {
        (new TrimStringsMiddleware())->handle($request, static fn (Request $r): Response => new Response());
    }
}
