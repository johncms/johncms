<?php

declare(strict_types=1);

namespace Johncms\Utility;

use Johncms\Http\Request;
use Johncms\Settings\SiteSettings;
use Johncms\View\Render;

class Pagination
{
    private int $perPage;
    private int $currentPage;
    private Request $request;
    private \Compolomus\Pagination\Pagination $pagination;

    public function __construct(private int $total, ?int $perPage = null, private string $pageParamName = 'page', ?int $currentPage = null)
    {
        $this->request = di(Request::class);
        $this->setPerPage($perPage);
        $this->setCurrentPage($currentPage);
        $this->pagination = new \Compolomus\Pagination\Pagination($this->currentPage, $this->perPage, $this->total, 2);
    }

    private function setCurrentPage(?int $currentPage = null): void
    {
        if ($currentPage) {
            $this->currentPage = $currentPage;
        } else {
            $this->currentPage = $this->request->getQuery($this->pageParamName, 1, FILTER_VALIDATE_INT);
            if ($this->currentPage < 1) {
                $this->currentPage = 1;
            }
        }
    }

    private function setPerPage(?int $perPage): void
    {
        $this->perPage = $perPage ?? di(SiteSettings::class)->getPerPage();
    }

    private function buildUrl(int $page): string
    {
        return $this->request->getQueryString(additionalParams: [$this->pageParamName => $page]);
    }

    public function getOffset(): int
    {
        return $this->pagination->getOffset();
    }

    public function getTotalPages(): int
    {
        return $this->pagination->getTotalPages();
    }

    public function getLimit(): int
    {
        $limit = $this->pagination->getLimit();
        return min($this->total, $limit);
    }

    public function getPages(): array
    {
        if ($this->getTotalPages() < 2) {
            return [];
        }

        $items = $this->pagination->get();
        $items = array_map(fn($item) => [
            'active' => $item === $this->currentPage,
            'name'   => $item,
            'url'    => is_numeric($item) ? $this->buildUrl($item) : '',
        ], $items);

        $prev = $this->pagination->getPreviousPage();
        $prevPage = [];
        if ($prev) {
            $prevPage[] = [
                'active' => false,
                'name'   => '&lt;&lt;',
                'url'    => $this->buildUrl($prev),
            ];
        }

        $next = $this->pagination->getNextPage();
        $nextPage = [];
        if ($next) {
            $nextPage[] = [
                'active' => false,
                'name'   => '&gt;&gt;',
                'url'    => $this->buildUrl($next),
            ];
        }

        return [...$prevPage, ...$items, ...$nextPage];
    }

    public function render(): string
    {
        $render = di(Render::class);
        return $render->render(
            'system::app/pagination',
            [
                'items' => $this->getPages(),
            ]
        );
    }
}
