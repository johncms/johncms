<?php

declare(strict_types=1);

namespace Johncms\Modules\Library\Application\Controllers;

use Johncms\Http\PageMeta;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Http\View\ViewResponse;
use Johncms\Modules\Library\Domain\Models\LibraryCategory;
use Johncms\Modules\Library\Domain\Repository\LibraryTextRepositoryInterface;
use Johncms\NavChain;
use Johncms\Modules\Library\Application\Services\Rating;
use Johncms\Utils\DateFormatterInterface;

final readonly class NewArticlesController
{
    public function __construct(
        private NavChain $navChain,
        private DateFormatterInterface $dateFormatter,
        private LibraryTextRepositoryInterface $repository,
        private PaginationFactory $paginationFactory,
        private PaginationGuard $paginationGuard,
    ) {
    }

    public function __invoke(): ViewResponse
    {
        $total = $this->repository->countNew();

        $pagination = $this->paginationFactory->create($total);
        $redirectUrl = $this->paginationGuard->redirectUrl($pagination);
        if ($redirectUrl !== null) {
            redirect($redirectUrl);
        }

        $pageTitle = __('New Articles');
        $meta = new PageMeta($pageTitle . ' — ' . __('Library'), $pagination->getCurrentPage());

        $this->navChain->add(__('Library'), '/library/');
        $this->navChain->add($pageTitle);

        $texts = $total ? $this->repository->getNew($pagination->getCurrentPage(), $pagination->getPerPage()) : collect();

        $items = [];
        foreach ($texts as $text) {
            $rate     = new Rating($text->id);
            $category = LibraryCategory::query()->find($text->cat_id);

            $items[] = [
                'id'          => $text->id,
                'url'         => $text->url,
                'name'        => $text->name,
                'announce'    => $text->announce,
                'cat_url'     => $category !== null ? $category->url : '/library/',
                'cat_name'    => $category?->name ?? '',
                'uploader_id' => $text->uploader_id,
                'uploader'    => $text->uploader,
                'date'        => $this->dateFormatter->format($text->time),
                'rate'        => $rate->getRate(),
                'votes'       => $rate->getVotesCount(),
                'comments'    => (bool) $text->comments,
                'comm_count'  => $text->comm_count,
            ];
        }

        return new ViewResponse('@library/public/new.twig', [
            'title'       => $meta->title,
            'page_title'  => $pageTitle,
            'description' => $meta->description,
            'total'       => $total,
            'articles'    => $items,
            'pagination'  => $pagination->hasPages() ? $pagination->render() : null,
        ]);
    }
}
