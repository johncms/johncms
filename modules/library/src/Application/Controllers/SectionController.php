<?php

declare(strict_types=1);

namespace Johncms\Modules\Library\Application\Controllers;

use Johncms\Http\PageMeta;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Modules\Library\Application\Services\LibraryCategoryPathService;
use Johncms\Modules\Library\Domain\Models\LibraryCategory;
use Johncms\Modules\Library\Domain\Models\LibraryText;
use Johncms\NavChain;
use Johncms\System\Legacy\Tools;
use Johncms\System\View\Render;
use Johncms\Users\User;
use Johncms\Modules\Library\Application\Services\Hashtags;
use Johncms\Modules\Library\Application\Services\Rating;
use Johncms\Modules\Library\Application\Services\Tree;
use Johncms\Modules\Library\Application\Services\Utils;
use Johncms\Modules\Library\Application\Services\ViewHelper;

final readonly class SectionController
{
    public function __construct(
        private Render $render,
        private NavChain $navChain,
        private Tools $tools,
        private User $currentUser,
        private LibraryCategoryPathService $categoryPathService,
        private PaginationFactory $paginationFactory,
        private PaginationGuard $paginationGuard,
    ) {
    }

    public function __invoke(string $categoryPath): string
    {
        $category = $this->categoryPathService->findCategoryByPath($categoryPath);

        if ($category === null) {
            http_response_code(404);
            return $this->render->render('system::pages/result', [
                'title'   => __('Library'),
                'type'    => 'alert-danger',
                'message' => __('Section does not exist'),
            ]);
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

    private function renderSectionsList(LibraryCategory $category, bool $isAdmin): string
    {
        $id    = $category->id;
        $total = LibraryCategory::query()->where('parent', $id)->count();

        $pagination = $this->paginationFactory->create($total);
        $redirectUrl = $this->paginationGuard->redirectUrl($pagination);
        if ($redirectUrl !== null) {
            redirect($redirectUrl);
        }

        $meta = new PageMeta($category->name . ' — ' . __('Library'), $pagination->getCurrentPage());
        $this->render->addData([
            'title'      => $meta->title,
            'page_title' => $category->name,
        ]);

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
                'id'                    => $section->id,
                'url'                   => $section->url,
                'name'                  => $this->tools->checkout($section->name),
                'description'           => $section->description ? $this->tools->checkout($section->description) : null,
                'libCounter'            => Utils::libCounter($section->id, $section->dir),
                'sectionListAdminPanel' => ViewHelper::sectionsListAdminPanel($id, $section->id, $i, $total),
            ];
        }

        return $this->render->render('library::sectionslist', [
            'total'        => $total,
            'admin'        => $isAdmin,
            'id'           => $id,
            'category_url' => $category->url,
            'list'         => $list,
            'pagination'   => $pagination->render(),
        ]);
    }

    private function renderBookList(LibraryCategory $category, bool $isAdmin): string
    {
        $id    = $category->id;
        $total = LibraryText::query()->where('cat_id', $id)->where('premod', 1)->count();

        $pagination = $this->paginationFactory->create($total);
        $redirectUrl = $this->paginationGuard->redirectUrl($pagination);
        if ($redirectUrl !== null) {
            redirect($redirectUrl);
        }

        $meta = new PageMeta($category->name . ' — ' . __('Library'), $pagination->getCurrentPage());
        $this->render->addData([
            'title'      => $meta->title,
            'page_title' => $category->name,
        ]);

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
            $uploader = $article->uploader_id
                ? '<a href="' . config('johncms')['homeurl'] . '/profile/' . $article->uploader_id . '">' . $this->tools->checkout($article->uploader) . '</a>'
                : $this->tools->checkout($article->uploader);

            $rate = new Rating($article->id);
            $list[] = [
                'id'         => $article->id,
                'url'        => $article->url,
                'name'       => $article->name,
                'announce'   => $this->tools->checkout($article->announce),
                'who'        => $uploader . '&nbsp;(' . $this->tools->displayDate($article->time) . ')',
                'ratingView' => $rate->viewRate(1),
                'tags'       => (new Hashtags($article->id))->getAllStatTags(1) ?: null,
            ];
        }

        return $this->render->render('library::booklist', [
            'total'      => $total,
            'admin'      => $isAdmin,
            'moderMenu'  => $moderMenu,
            'id'         => $id,
            'category_url' => $category->url,
            'list'       => $list,
            'pagination' => $pagination->render(),
        ]);
    }
}
