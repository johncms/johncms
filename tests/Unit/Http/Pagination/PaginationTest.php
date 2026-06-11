<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Pagination;

use Johncms\Http\Pagination\Pagination;
use Johncms\System\Http\Request;
use Johncms\System\View\Render;
use PHPUnit\Framework\TestCase;

final class PaginationTest extends TestCase
{
    public function testTotalPagesCalculation(): void
    {
        self::assertSame(1, $this->makePagination(total: 0)->getTotalPages());
        self::assertSame(1, $this->makePagination(total: 10)->getTotalPages());
        self::assertSame(2, $this->makePagination(total: 11)->getTotalPages());
        self::assertSame(10, $this->makePagination(total: 95)->getTotalPages());
        self::assertSame(10, $this->makePagination(total: 100)->getTotalPages());
    }

    public function testPerPageIsClampedToMinimumOne(): void
    {
        $pagination = $this->makePagination(total: 5, perPage: 0);

        self::assertSame(1, $pagination->getPerPage());
        self::assertSame(5, $pagination->getTotalPages());
    }

    public function testCurrentPageIsClampedToValidRange(): void
    {
        self::assertSame(5, $this->makePagination(total: 50, currentPage: 99)->getCurrentPage());
        self::assertSame(1, $this->makePagination(total: 50, currentPage: 0)->getCurrentPage());
        self::assertSame(1, $this->makePagination(total: 50, currentPage: -3)->getCurrentPage());
        self::assertSame(3, $this->makePagination(total: 50, currentPage: 3)->getCurrentPage());
    }

    public function testOffsetIsCalculatedFromCurrentPage(): void
    {
        self::assertSame(0, $this->makePagination(total: 50, currentPage: 1)->getOffset());
        self::assertSame(20, $this->makePagination(total: 50, currentPage: 3)->getOffset());
        self::assertSame(40, $this->makePagination(total: 50, currentPage: 5)->getOffset());
    }

    public function testHasPages(): void
    {
        self::assertFalse($this->makePagination(total: 0)->hasPages());
        self::assertFalse($this->makePagination(total: 10)->hasPages());
        self::assertTrue($this->makePagination(total: 11)->hasPages());
    }

    public function testUrlForFirstPageStripsPageParameter(): void
    {
        $pagination = $this->makePagination(total: 50);

        self::assertSame('/guestbook/', $pagination->getUrl(1));
        self::assertSame('/guestbook/', $pagination->getUrl(0));
        self::assertSame('/guestbook/?page=3', $pagination->getUrl(3));
    }

    public function testItemsAreEmptyWhenThereIsOnlyOnePage(): void
    {
        self::assertSame([], $this->makePagination(total: 10)->getItems());
        self::assertSame([], $this->makePagination(total: 0)->getItems());
    }

    public function testItemsForSmallNumberOfPages(): void
    {
        $items = $this->makePagination(total: 50, currentPage: 3)->getItems();

        self::assertSame(
            [Pagination::TYPE_PREV, Pagination::TYPE_PAGE, Pagination::TYPE_PAGE, Pagination::TYPE_PAGE, Pagination::TYPE_PAGE, Pagination::TYPE_PAGE, Pagination::TYPE_NEXT],
            array_column($items, 'type')
        );
        self::assertSame([2, 1, 2, 3, 4, 5, 4], array_column($items, 'page'));
        self::assertSame([3], array_column(array_filter($items, static fn (array $item): bool => $item['active']), 'page'));
        self::assertSame('/guestbook/?page=2', $items[0]['url']);
        self::assertSame('/guestbook/', $items[1]['url']);
        self::assertSame('/guestbook/?page=4', $items[6]['url']);
    }

    public function testItemsWindowWithEllipsis(): void
    {
        $items = $this->makePagination(total: 200, currentPage: 10)->getItems();

        $expected = [
            [Pagination::TYPE_PREV, 9],
            [Pagination::TYPE_PAGE, 1],
            [Pagination::TYPE_ELLIPSIS, null],
            [Pagination::TYPE_PAGE, 8],
            [Pagination::TYPE_PAGE, 9],
            [Pagination::TYPE_PAGE, 10],
            [Pagination::TYPE_PAGE, 11],
            [Pagination::TYPE_PAGE, 12],
            [Pagination::TYPE_ELLIPSIS, null],
            [Pagination::TYPE_PAGE, 20],
            [Pagination::TYPE_NEXT, 11],
        ];

        self::assertSame($expected, array_map(static fn (array $item): array => [$item['type'], $item['page']], $items));
        self::assertSame([10], array_column(array_filter($items, static fn (array $item): bool => $item['active']), 'page'));
    }

    public function testPreviousIsDisabledOnFirstPage(): void
    {
        $items = $this->makePagination(total: 50, currentPage: 1)->getItems();
        $first = $items[0];

        self::assertSame(Pagination::TYPE_PREV, $first['type']);
        self::assertNull($first['url']);
        self::assertSame('/guestbook/?page=2', $items[array_key_last($items)]['url']);
    }

    public function testNextIsDisabledOnLastPage(): void
    {
        $items = $this->makePagination(total: 50, currentPage: 5)->getItems();
        $last = $items[array_key_last($items)];

        self::assertSame(Pagination::TYPE_NEXT, $last['type']);
        self::assertNull($last['url']);
        self::assertSame('/guestbook/?page=4', $items[0]['url']);
    }

    public function testRenderUsesPaginationTemplate(): void
    {
        $renderer = $this->createMock(Render::class);
        $pagination = $this->makePagination(total: 50, currentPage: 2, renderer: $renderer);

        $renderer
            ->expects(self::once())
            ->method('render')
            ->with('system::app/pagination', ['items' => $pagination->getItems()])
            ->willReturn('<nav></nav>');

        self::assertSame('<nav></nav>', $pagination->render());
    }

    private function makePagination(
        int $total,
        int $perPage = 10,
        int $currentPage = 1,
        ?Render $renderer = null,
    ): Pagination {
        $request = $this->createMock(Request::class);
        $request->method('getQueryString')->willReturnCallback(
            static fn (array $removeParams = [], array $addParams = []): string => $addParams === []
                ? '/guestbook/'
                : '/guestbook/?' . http_build_query($addParams)
        );

        return new Pagination(
            request:     $request,
            renderer:    $renderer ?? $this->createMock(Render::class),
            total:       $total,
            perPage:     $perPage,
            currentPage: $currentPage,
        );
    }
}
