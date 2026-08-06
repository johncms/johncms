<?php

declare(strict_types=1);

namespace Johncms\Modules\Library\Application\Controllers;

use Johncms\Http\PageMeta;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Http\View\ViewResponse;
use Johncms\Modules\Library\Application\Services\LibraryCategoryPathService;
use Johncms\Modules\Library\Domain\Models\LibraryCategory;
use Johncms\Modules\Library\Domain\Models\LibraryText;
use Johncms\NavChain;
use Johncms\Users\User;
use Johncms\Modules\Library\Application\Services\Rating;
use Johncms\Modules\Library\Application\Services\Tree;
use Johncms\Modules\Library\Application\Services\Utils;
use Johncms\Utils\DateFormatterInterface;
use Symfony\Component\HttpFoundation\Response;

final readonly class SectionController
{
    public function __construct(
        private NavChain $navChain,
        private DateFormatterInterface $dateFormatter,
        private User $currentUser,
        private LibraryCategoryPathService $categoryPathService,
        private PaginationFactory $paginationFactory,
        private PaginationGuard $paginationGuard,
    ) {
    }

    public function __invoke(string $categoryPath): ViewResponse
    {
        $category = $this->categoryPathService->findCategoryByPath($categoryPath);

        if ($category === null) {
            return new ViewResponse(
                '@theme/pages/result.twig',
                [
                    'title'   => __('Library'),
                    'type'    => 'alert-danger',
                    'message' => __('Section does not exist'),
                ],
                Response::HTTP_NOT_FOUND
            );
        }

        $id = $category->id;

        $this->navChain->add(__('Library'), '/library/');
        $dirNav = new Tree($id);
        $dirNav->processNavPanel();
        $dirNav->printNavPanel();

        $isAdmin = $this->currentUser->rights > 4;

        if ($category->dir) {
            return $this->renderSectionsList($category, $isAdmin);
        }

        return $this->renderBookList($category, $isAdmin);
    }

    private function renderSectionsList(LibraryCategory $category, bool $isAdmin): ViewResponse
    {
        $id    = $category->id;
        $total = LibraryCategory::query()->where('parent', $id)->count();

        $pagination = $this->paginationFactory->create($total);
        $redirectUrl = $this->paginationGuard->redirectUrl($pagination);
        if ($redirectUrl !== null) {
            redirect($redirectUrl);
        }

        $meta = new PageMeta($category->name . ' — ' . __('Library'), $pagination->getCurrentPage());

        $offset   = $pagination->getOffset();
        $sections = LibraryCategory::query()
            ->where('parent', $id)
            ->orderBy('pos')
            ->offset($offset)
            ->limit($pagination->getPerPage())
            ->get();

        $list = [];
        $i    = $offset;
        foreach ($sections as $section) {
            $i++;
            $list[] = [
                'id'          => $section->id,
                'url'         => $section->url,
                'name'        => $section->name,
                'description' => $section->description,
                'counter'     => Utils::libCounter($section->id, $section->dir),
                'position'    => $i,
            ];
        }

        return new ViewResponse('@library/public/sections.twig', [
            'title'      => $meta->title,
            'page_title' => $category->name,
            'total'      => $total,
            'admin'      => $isAdmin,
            'id'         => $id,
            'sections'   => $list,
            'pagination' => $pagination->hasPages() ? $pagination->render() : null,
        ]);
    }

    private function renderBookList(LibraryCategory $category, bool $isAdmin): ViewResponse
    {
        $id    = $category->id;
        $total = LibraryText::query()->where('cat_id', $id)->where('premod', 1)->count();

        $pagination = $this->paginationFactory->create($total);
        $redirectUrl = $this->paginationGuard->redirectUrl($pagination);
        if ($redirectUrl !== null) {
            redirect($redirectUrl);
        }

        $meta = new PageMeta($category->name . ' — ' . __('Library'), $pagination->getCurrentPage());

        $moderMenu = $isAdmin || ($this->currentUser->isValid() && (int) $category->user_add > 0);
        $offset    = $pagination->getOffset();

        $articles = LibraryText::query()
            ->select(['id', 'cat_id', 'slug', 'name', 'time', 'uploader', 'uploader_id', 'count_views', 'comm_count', 'comments', 'announce'])
            ->where('cat_id', $id)
            ->where('premod', 1)
            ->orderByDesc('id')
            ->offset($offset)
            ->limit($pagination->getPerPage())
            ->get();

        $list = [];
        foreach ($articles as $article) {
            $rate   = new Rating($article->id);
            $list[] = [
                'id'          => $article->id,
                'url'         => $article->url,
                'name'        => $article->name,
                'announce'    => $article->announce,
                'uploader_id' => $article->uploader_id,
                'uploader'    => $article->uploader,
                'date'        => $this->dateFormatter->format($article->time),
                'rate'        => $rate->getRate(),
                'votes'       => $rate->getVotesCount(),
            ];
        }

        return new ViewResponse('@library/public/articles.twig', [
            'title'       => $meta->title,
            'page_title'  => $category->name,
            'total'       => $total,
            'admin'       => $isAdmin,
            'moder_menu'  => $moderMenu,
            'id'          => $id,
            'articles'    => $list,
            'pagination'  => $pagination->hasPages() ? $pagination->render() : null,
        ]);
    }
}
