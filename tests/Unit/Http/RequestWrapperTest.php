<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use Johncms\Http\Request;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

/**
 * Contract tests for the HttpFoundation-based Request wrapper (plan stage 1a).
 *
 * These mirror the legacy PSR-7 contract (RequestInputBehaviorTest) with two intentional
 * differences that the plan calls out:
 *   - the wrapper does NOT trim (trimming moves to TrimStringsMiddleware, stage 1a-bis);
 *   - invalid integers fall back to the default via the soft bodyInt()/queryInt() mode.
 */
final class RequestWrapperTest extends TestCase
{
    private function makeRequest(
        array $query = [],
        array $post = [],
        array $cookies = [],
        array $server = [],
        ?string $content = null,
    ): Request {
        return new Request($query, $post, [], $cookies, [], $server, $content);
    }

    public function testBodyReadsFormEncodedValue(): void
    {
        $request = $this->makeRequest(post: ['message' => 'hello']);

        self::assertSame('hello', $request->body('message'));
    }

    public function testBodyReadsJsonPayload(): void
    {
        $request = $this->makeRequest(
            server: ['CONTENT_TYPE' => 'application/json'],
            content: '{"message":"from json"}',
        );

        self::assertSame('from json', $request->body('message'));
    }

    public function testBodyReturnsDefaultForMissingKey(): void
    {
        $request = $this->makeRequest(post: ['a' => 'x']);

        self::assertSame('', $request->body('missing'));
        self::assertSame('DEF', $request->body('missing', 'DEF'));
    }

    public function testBodyDoesNotTrim(): void
    {
        // Divergence from the legacy Request: trimming now lives in middleware, not here.
        $request = $this->makeRequest(post: ['a' => '  spaced  ']);

        self::assertSame('  spaced  ', $request->body('a'));
    }

    public function testEmptyStringIsPreservedAndDistinctFromMissingKey(): void
    {
        $request = $this->makeRequest(post: ['empty' => '']);

        self::assertSame('', $request->body('empty', 'DEF'));
        self::assertSame('DEF', $request->body('absent', 'DEF'));
    }

    public function testBodyIntReadsValidInteger(): void
    {
        $request = $this->makeRequest(post: ['id' => '42']);

        self::assertSame(42, $request->bodyInt('id'));
    }

    public function testBodyIntSoftlyFallsBackForNonNumeric(): void
    {
        $request = $this->makeRequest(post: ['id' => 'abc']);

        self::assertSame(0, $request->bodyInt('id'));
        self::assertSame(7, $request->bodyInt('id', 7));
    }

    public function testBodyIntReturnsDefaultForMissingKey(): void
    {
        $request = $this->makeRequest();

        self::assertSame(0, $request->bodyInt('id'));
    }

    public function testBodyListReturnsArray(): void
    {
        $request = $this->makeRequest(post: ['users' => ['10', '20']]);

        self::assertSame(['10', '20'], $request->bodyList('users'));
    }

    public function testBodyListReturnsEmptyArrayForMissingKey(): void
    {
        $request = $this->makeRequest();

        self::assertSame([], $request->bodyList('users'));
    }

    public function testBodyIntsFiltersToIntegers(): void
    {
        $request = $this->makeRequest(post: ['attached_files' => ['1', '2', '3']]);

        self::assertSame([1, 2, 3], $request->bodyInts('attached_files'));
    }

    public function testHasBodyDetectsPresenceIncludingEmptyValue(): void
    {
        $request = $this->makeRequest(post: ['agree' => '', 'other' => 'x']);

        self::assertTrue($request->hasBody('agree'));
        self::assertTrue($request->hasBody('other'));
        self::assertFalse($request->hasBody('missing'));
    }

    public function testQueryHelpersReadFromQueryString(): void
    {
        $request = $this->makeRequest(query: ['page' => '3', 'q' => 'text']);

        self::assertSame('text', $request->queryParam('q'));
        self::assertSame(3, $request->queryInt('page'));
        self::assertSame('DEF', $request->queryParam('missing', 'DEF'));
    }

    public function testQueryIntSoftlyFallsBackForNonNumeric(): void
    {
        $request = $this->makeRequest(query: ['page' => 'abc']);

        self::assertSame(1, $request->queryInt('page', 1));
    }

    public function testQueryIntsFiltersToIntegers(): void
    {
        $request = $this->makeRequest(query: ['ids' => ['4', '5']]);

        self::assertSame([4, 5], $request->queryInts('ids'));
    }

    public function testBodyAndQueryReadFromDistinctSources(): void
    {
        $request = $this->makeRequest(query: ['src' => 'query'], post: ['src' => 'body']);

        self::assertSame('query', $request->queryParam('src'));
        self::assertSame('body', $request->body('src'));
    }

    public function testIsPostReflectsHttpMethod(): void
    {
        $post = Request::create('/', 'POST');
        $get = Request::create('/', 'GET');

        self::assertTrue($post->isPost());
        self::assertFalse($get->isPost());
    }

    public function testScalarBodyRequestedAsListThrowsBadRequest(): void
    {
        // HttpFoundation rejects the ?users=scalar type-confusion attack that the legacy
        // recursive filterVar silently tolerated.
        $request = $this->makeRequest(post: ['users' => 'not-an-array']);

        $this->expectException(BadRequestException::class);
        $request->bodyList('users');
    }

    public function testBodyIntRejectsArrayInScalarParameter(): void
    {
        // ?id[]=1 in a scalar int parameter is a type-confusion attempt: it must surface as
        // BadRequestException (kernel → 400), not be swallowed by the soft fallback.
        // This is the single intentional behavior change of the migration.
        $request = $this->makeRequest(post: ['id' => ['1', '2']]);

        $this->expectException(BadRequestException::class);
        $request->bodyInt('id');
    }

    public function testQueryIntRejectsArrayInScalarParameter(): void
    {
        $request = $this->makeRequest(query: ['page' => ['1']]);

        $this->expectException(BadRequestException::class);
        $request->queryInt('page');
    }
}
