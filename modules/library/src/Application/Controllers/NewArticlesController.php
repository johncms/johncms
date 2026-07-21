<?php

declare(strict_types=1);

namespace Johncms\Modules\Library\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Http\PageMeta;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Modules\Library\Domain\Models\LibraryCategory;
use Johncms\Modules\Library\Domain\Repository\LibraryTextRepositoryInterface;
use Johncms\NavChain;
use Johncms\System\Legacy\Tools;
use Johncms\System\View\Render;
use Johncms\Modules\Library\Application\Services\Hashtags;
use Johncms\Modules\Library\Application\Services\Rating;

final readonly class NewArticlesController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private NavChain $navChain,
        private Tools $tools,
        private LibraryTextRepositoryInterface $repository,
        private PaginationFactory $paginationFactory,
        private PaginationGuard $paginationGuard,
    ) {
        $this->controllerContext->initModule('library');
        $this->render->addFolder('libraryHelpers', MODULES_PATH . 'library/templates/helpers/');
    }

    public function __invoke(): string
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

        $this->render->addData([
            'title'       => $meta->title,
            'page_title'  => $pageTitle,
            'description' => $meta->description,
        ]);

        $texts = $total ? $this->repository->getNew($pagination->getCurrentPage(), $pagination->getPerPage()) : collect();

        $items = [];
        foreach ($texts as $text) {
            $obj = new Hashtags($text->id);
            $rate = new Rating($text->id);
            $category = LibraryCategory::query()->find($text->cat_id);

            $uploader = $text->uploader_id
                ? '<a href="' . config('johncms')['homeurl'] . '/profile/' . $text->uploader_id . '">' . $this->tools->checkout($text->uploader) . '</a>'
                : $this->tools->checkout($text->uploader);

            $items[] = [
                'id'           => $text->id,
                'url'          => $text->url,
                'name'         => $this->tools->checkout($text->name),
                'announce'     => $this->tools->checkout($text->announce),
                'cover'        => file_exists(UPLOAD_PATH . 'library/images/small/' . $text->id . '.png'),
                'tags'         => $obj->getAllStatTags() ? $obj->getAllStatTags(1) : null,
                'ratingView'   => $rate->viewRate(1),
                'who'          => $uploader . ' (' . $this->tools->displayDate($text->time) . ')',
                'cat_id'       => $text->cat_id,
                'cat_url'      => $category ? $category->url : '/library/',
                'catalog_name' => $category ? $this->tools->checkout($category->name) : '',
                'comments'     => $text->comments,
                'comm_count'   => $text->comm_count,
            ];
        }

        return $this->render->render('library::new', [
            'total'      => $total,
            'pagination' => $pagination->render(),
            'items'      => $items,
        ]);
    }
}
