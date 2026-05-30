<?php

declare(strict_types=1);

namespace Johncms\Modules\Library\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Http\PageMeta;
use Johncms\Modules\Library\Domain\Models\LibraryCategory;
use Johncms\Modules\Library\Domain\Repository\LibraryTextRepositoryInterface;
use Johncms\NavChain;
use Johncms\System\Http\Request;
use Johncms\System\Legacy\Tools;
use Johncms\System\View\Render;
use Johncms\Modules\Library\Application\Services\Hashtags;
use Johncms\Modules\Library\Application\Services\Rating;

final readonly class TopController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private NavChain $navChain,
        private Request $request,
        private Tools $tools,
        private LibraryTextRepositoryInterface $repository,
    ) {
        $this->controllerContext->initModule('library');
        $this->render->addFolder('libraryHelpers', MODULES_PATH . 'library/templates/helpers/');
    }

    public function __invoke(): string
    {
        $sort = $this->request->getQuery('sort', 'read');
        $sort = in_array($sort, ['read', 'rating', 'comm'], true) ? $sort : 'read';

        $pageTitle = __('Rating articles');
        $meta = new PageMeta($pageTitle . ' — ' . __('Library'), 1);

        $this->navChain->add(__('Library'), '/library/');
        $this->navChain->add($pageTitle);

        $this->render->addData([
            'title'       => $meta->title,
            'page_title'  => $pageTitle,
            'description' => $meta->description,
        ]);

        $filters = [
            'read'   => ['name' => __('Most readings'), 'url' => '/library/top',             'active' => $sort === 'read'],
            'rating' => ['name' => __('By rating'),     'url' => '/library/top?sort=rating', 'active' => $sort === 'rating'],
            'comm'   => ['name' => __('By comments'),   'url' => '/library/top?sort=comm',   'active' => $sort === 'comm'],
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
            $obj = new Hashtags($text->id);
            $rate = new Rating($text->id);
            $category = LibraryCategory::query()->find($text->cat_id);

            $uploader = $text->uploader_id
                ? '<a href="' . config('johncms')['homeurl'] . '/profile/' . $text->uploader_id . '">' . $this->tools->checkout($text->uploader) . '</a>'
                : $this->tools->checkout($text->uploader);

            $items[] = [
                'id'          => $text->id,
                'url'         => $text->url,
                'name'        => $this->tools->checkout($text->name),
                'announce'    => $this->tools->checkout($text->announce, 0, 0),
                'cover'       => file_exists(UPLOAD_PATH . 'library/images/small/' . $text->id . '.png'),
                'tags'        => $obj->getAllStatTags() ? $obj->getAllStatTags(1) : null,
                'ratingView'  => $rate->viewRate(1),
                'who'         => $uploader . ' (' . $this->tools->displayDate($text->time) . ')',
                'cat_id'      => $text->cat_id,
                'cat_url'     => $category ? $category->url : '/library/',
                'cat_name'    => $category ? $this->tools->checkout($category->name) : '',
                'comments'    => $text->comments,
                'comm_count'  => $text->comm_count,
            ];
        }

        return $this->render->render('library::top', [
            'data'  => ['filters' => $filters],
            'total' => $total,
            'items' => $items,
        ]);
    }
}
