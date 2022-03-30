<?php

declare(strict_types=1);

namespace Johncms\Utility;

use JetBrains\PhpStorm\Pure;
use Johncms\Http\Request;
use Johncms\Settings\SiteSettings;
use Johncms\View\Render;

class Pagination
{
    private int $total;
    private string $pageParamName;
    private int $perPage;
    private int $currentPage;
    private Request $request;
    private \Compolomus\Pagination\Pagination $pagination;

    public function __construct(int $total, ?int $perPage = null, string $pageParamName = 'page', ?int $currentPage = null)
    {
        $this->total = $total;
        $this->pageParamName = $pageParamName;
        $this->request = di(Request::class);
        if (! $perPage) {
            $this->perPage = di(SiteSettings::class)->getPerPage();
        } else {
            $this->perPage = $perPage;
        }
        $this->setCurrentPage($currentPage);
        $this->pagination = new \Compolomus\Pagination\Pagination($this->currentPage, $this->perPage, $this->total, 2);
    }

    public function setCurrentPage(?int $currentPage = null): static
    {
        if ($currentPage) {
            $this->currentPage = $currentPage;
        } else {
            $this->currentPage = $this->request->getQuery($this->pageParamName, 1, FILTER_VALIDATE_INT);
            if ($this->currentPage < 1) {
                $this->currentPage = 1;
            }
        }
        return $this;
    }

    private function buildUrl(int $page): string
    {
        return $this->request->getQueryString(additionalParams: [$this->pageParamName => $page]);
    }

    #[Pure]
    public function getOffset(): int
    {
        return $this->pagination->getOffset();
    }

    #[Pure]
    public function getLimit(): int
    {
        $limit = $this->pagination->getLimit();
        return min($this->total, $limit);
    }

    public function getPages(): array
    {
        $items = $this->pagination->get();
        return array_map(function ($item) {
            return [
                'active' => $item === $this->currentPage,
                'name'   => $item,
                'url'    => is_numeric($item) ? $this->buildUrl($item) : '',
            ];
        }, $items);
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
