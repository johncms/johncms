<?php

declare(strict_types=1);

namespace Johncms\Modules\Library\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Http\PageMeta;
use Johncms\Http\View\ViewResponse;
use Johncms\Modules\Library\Domain\Models\LibraryCategory;
use Johncms\Modules\Library\Domain\Repository\LibraryTextRepositoryInterface;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\Modules\Library\Application\Services\Rating;
use Johncms\Utils\DateFormatterInterface;

final readonly class TopController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private NavChain $navChain,
        private DateFormatterInterface $dateFormatter,
        private LibraryTextRepositoryInterface $repository,
    ) {
        $this->controllerContext->initModule('library');
    }

    public function __invoke(Request $request): ViewResponse
    {
        $sort = $request->queryParam('sort', 'read');
        $sort = in_array($sort, ['read', 'rating', 'comm'], true) ? $sort : 'read';

        $pageTitle = __('Rating articles');
        $meta = new PageMeta($pageTitle . ' — ' . __('Library'), 1);

        $this->navChain->add(__('Library'), '/library/');
        $this->navChain->add($pageTitle);

        $filters = [
            ['name' => __('Most readings'), 'url' => '/library/top',             'active' => $sort === 'read'],
            ['name' => __('By rating'),     'url' => '/library/top?sort=rating', 'active' => $sort === 'rating'],
            ['name' => __('By comments'),   'url' => '/library/top?sort=comm',   'active' => $sort === 'comm'],
        ];

        if ($sort === 'rating') {
            $total = $this->repository->countTopByRating();
            $texts = $total ? $this->repository->getTopByRating(20) : collect();
        } else {
            $field = $sort === 'comm' ? 'comm_count' : 'count_views';
            $total = $this->repository->countTopByField($field);
            $texts = $total ? $this->repository->getTopByField($field, 20) : collect();
        }

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

        return new ViewResponse('@library/public/top.twig', [
            'title'       => $meta->title,
            'page_title'  => $pageTitle,
            'description' => $meta->description,
            'filters'     => $filters,
            'total'       => $total,
            'articles'    => $items,
        ]);
    }
}
