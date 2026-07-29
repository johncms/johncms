<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Pagination;

use Johncms\Http\Pagination\Pagination;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Http\QueryStringBuilder;
use Johncms\Http\Request;
use Johncms\System\View\Render;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;

final class PaginationGuardTest extends TestCase
{
    public function testNoRedirectWhenPageParameterIsAbsent(): void
    {
        $request = $this->makeRequest([]);

        self::assertNull($this->guard($request)->redirectUrl($this->makePagination($request)));
    }

    public function testNoRedirectForValidPageInRange(): void
    {
        $request = $this->makeRequest(['page' => '3']);

        self::assertNull($this->guard($request)->redirectUrl($this->makePagination($request)));
    }

    public function testExplicitFirstPageRedirectsToUrlWithoutPageParameter(): void
    {
        $request = $this->makeRequest(['page' => '1']);

        self::assertSame('/guestbook/', $this->guard($request)->redirectUrl($this->makePagination($request)));
    }

    public function testInvalidPageValueRedirectsToUrlWithoutPageParameter(): void
    {
        foreach (['abc', '0', '-5', '2.5'] as $value) {
            $request = $this->makeRequest(['page' => $value]);

            self::assertSame(
                '/guestbook/',
                $this->guard($request)->redirectUrl($this->makePagination($request)),
                'page=' . $value
            );
        }
    }

    public function testArrayPageValueRedirectsToUrlWithoutPageParameter(): void
    {
        $request = $this->makeRequest(['page' => ['2']]);

        self::assertSame('/guestbook/', $this->guard($request)->redirectUrl($this->makePagination($request)));
    }

    public function testOutOfRangePageRedirectsToLastPage(): void
    {
        $request = $this->makeRequest(['page' => '99']);

        self::assertSame(
            '/guestbook/?page=5',
            $this->guard($request)->redirectUrl($this->makePagination($request, total: 50))
        );
    }

    public function testOutOfRangePageRedirectsToUrlWithoutParameterWhenOnlyOnePageExists(): void
    {
        $request = $this->makeRequest(['page' => '2']);

        self::assertSame(
            '/guestbook/',
            $this->guard($request)->redirectUrl($this->makePagination($request, total: 5))
        );
    }

    private function guard(Request $request): PaginationGuard
    {
        $requestStack = new RequestStack();
        $requestStack->push($request);

        return new PaginationGuard($requestStack);
    }

    /**
     * @param array<string, mixed> $queryParams
     */
    private function makeRequest(array $queryParams): Request
    {
        return new Request($queryParams);
    }

    private function makePagination(Request $request, int $total = 50): Pagination
    {
        return new Pagination(
            queryStringBuilder: new QueryStringBuilder(),
            renderer:           $this->createMock(Render::class),
            currentPath:        '/guestbook/',
            currentQuery:       $request->query->all(),
            total:              $total,
            perPage:            10,
            currentPage:        1,
        );
    }
}
