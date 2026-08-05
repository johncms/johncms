<?php

declare(strict_types=1);

namespace Johncms\Http\Pagination;

use Compolomus\Pagination\Pagination as PaginationCalculator;
use Johncms\Http\QueryStringBuilder;
use Johncms\System\View\Render;
use Twig\Markup;

final class Pagination
{
    public const TYPE_PREV = 'prev';
    public const TYPE_PAGE = 'page';
    public const TYPE_ELLIPSIS = 'ellipsis';
    public const TYPE_NEXT = 'next';

    private readonly int $perPage;
    private readonly int $currentPage;
    private readonly int $totalPages;
    private readonly PaginationCalculator $calculator;

    /**
     * @param array<string, mixed> $currentQuery Current query parameters (e.g. $_GET).
     */
    public function __construct(
        private readonly QueryStringBuilder $queryStringBuilder,
        private readonly Render $renderer,
        private readonly string $currentPath,
        private readonly array $currentQuery,
        private readonly int $total,
        int $perPage,
        int $currentPage,
        private readonly string $pageParamName = 'page',
        int $windowLength = 2,
    ) {
        $this->perPage = max(1, $perPage);
        $this->totalPages = max(1, (int) ceil($this->total / $this->perPage));
        $this->currentPage = min(max(1, $currentPage), $this->totalPages);
        $this->calculator = new PaginationCalculator($this->currentPage, $this->perPage, $this->total, max(0, $windowLength));
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function getTotalPages(): int
    {
        return $this->totalPages;
    }

    public function getCurrentPage(): int
    {
        return $this->currentPage;
    }

    public function getPerPage(): int
    {
        return $this->perPage;
    }

    public function getOffset(): int
    {
        return ($this->currentPage - 1) * $this->perPage;
    }

    public function getPageParamName(): string
    {
        return $this->pageParamName;
    }

    public function hasPages(): bool
    {
        return $this->totalPages > 1;
    }

    /**
     * Page 1 is canonicalized to the URL without the page parameter.
     */
    public function getUrl(int $page): string
    {
        if ($page <= 1) {
            return $this->queryStringBuilder->build($this->currentPath, $this->currentQuery, [$this->pageParamName]);
        }

        return $this->queryStringBuilder->build($this->currentPath, $this->currentQuery, [], [$this->pageParamName => $page]);
    }

    /**
     * @return list<array{type: string, page: int|null, url: string|null, active: bool}>
     */
    public function getItems(): array
    {
        if (! $this->hasPages()) {
            return [];
        }

        $items = [$this->navigationItem(self::TYPE_PREV, $this->calculator->getPreviousPage())];

        foreach ($this->calculator->get() as $element) {
            if (! is_int($element)) {
                $items[] = ['type' => self::TYPE_ELLIPSIS, 'page' => null, 'url' => null, 'active' => false];
                continue;
            }

            $items[] = [
                'type'   => self::TYPE_PAGE,
                'page'   => $element,
                'url'    => $this->getUrl($element),
                'active' => $element === $this->currentPage,
            ];
        }

        $items[] = $this->navigationItem(self::TYPE_NEXT, $this->calculator->getNextPage());

        return $items;
    }

    /**
     * A finished block of navigation links. It is markup by contract, so a Twig template prints
     * it with {{ pagination }} and needs no raw filter; a Plates template echoes it as before,
     * since Markup is a string when used as one.
     */
    public function render(): Markup
    {
        return new Markup(
            $this->renderer->render('system::app/pagination', ['items' => $this->getItems()]),
            'UTF-8'
        );
    }

    /**
     * @return array{type: string, page: int|null, url: string|null, active: bool}
     */
    private function navigationItem(string $type, ?int $page): array
    {
        return [
            'type'   => $type,
            'page'   => $page,
            'url'    => $page !== null ? $this->getUrl($page) : null,
            'active' => false,
        ];
    }
}
