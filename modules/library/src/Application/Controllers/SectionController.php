<?php

declare(strict_types=1);

namespace Johncms\Modules\Library\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Http\PageMeta;
use Johncms\Modules\Library\Domain\Models\LibraryCategory;
use Johncms\Modules\Library\Domain\Models\LibraryText;
use Johncms\NavChain;
use Johncms\System\Http\Request;
use Johncms\System\Legacy\Tools;
use Johncms\System\View\Render;
use Johncms\Users\User;
use Library\Hashtags;
use Library\Rating;
use Library\Tree;
use Library\Utils;
use Library\ViewHelper;

final readonly class SectionController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private NavChain $navChain,
        private Request $request,
        private Tools $tools,
        private User $currentUser,
    ) {
        $this->controllerContext->initModule('library');
    }

    public function __invoke(int $id): string
    {
        $category = LibraryCategory::query()->find($id);

        if ($category === null) {
            http_response_code(404);
            return $this->render->render('system::pages/result', [
                'title'   => __('Library'),
                'type'    => 'alert-danger',
                'message' => __('Section does not exist'),
            ]);
        }

        $this->navChain->add(__('Library'), '/library/');
        $dirNav = new Tree($id);
        $dirNav->processNavPanel();
        $dirNav->printNavPanel();

        $isAdmin = $this->currentUser->rights > 4;

        if ($category->dir) {
            return $this->renderSectionsList($id, $category->name, $isAdmin);
        }

        return $this->renderBookList($id, $category, $isAdmin);
    }

    private function renderSectionsList(int $id, string $name, bool $isAdmin): string
    {
        $page  = max(1, (int) $this->request->getQuery('page', 1));
        $kmess = $this->currentUser->config->kmess;
        $total = LibraryCategory::query()->where('parent', $id)->count();

        $meta = new PageMeta($name . ' — ' . __('Library'), $page);
        $this->render->addData([
            'title'      => $meta->title,
            'page_title' => $name,
        ]);

        $offset   = ($page - 1) * $kmess;
        $sections = LibraryCategory::query()
            ->where('parent', $id)
            ->orderBy('pos')
            ->offset($offset)
            ->limit($kmess)
            ->get();

        $list = [];
        $i    = $offset;
        foreach ($sections as $section) {
            $i++;
            $list[] = [
                'id'                    => $section->id,
                'name'                  => $this->tools->checkout($section->name),
                'description'           => $section->description ? $this->tools->checkout($section->description) : null,
                'libCounter'            => Utils::libCounter($section->id, $section->dir),
                'sectionListAdminPanel' => ViewHelper::sectionsListAdminPanel($id, $section->id, $i, $total),
            ];
        }

        return $this->render->render('library::sectionslist', [
            'total'      => $total,
            'admin'      => $isAdmin,
            'id'         => $id,
            'list'       => $list,
            'pagination' => $this->tools->displayPagination('/library/section/' . $id . '?', $offset, $total, $kmess),
        ]);
    }

    private function renderBookList(int $id, LibraryCategory $category, bool $isAdmin): string
    {
        $page  = max(1, (int) $this->request->getQuery('page', 1));
        $kmess = $this->currentUser->config->kmess;
        $total = LibraryText::query()->where('cat_id', $id)->where('premod', 1)->count();

        $meta = new PageMeta($category->name . ' — ' . __('Library'), $page);
        $this->render->addData([
            'title'      => $meta->title,
            'page_title' => $category->name,
        ]);

        $moderMenu = $isAdmin || ($this->currentUser->isValid() && (int) $category->user_add > 0);
        $offset    = ($page - 1) * $kmess;

        $articles = LibraryText::query()
            ->select(['id', 'name', 'time', 'uploader', 'uploader_id', 'count_views', 'comm_count', 'comments', 'announce'])
            ->where('cat_id', $id)
            ->where('premod', 1)
            ->orderByDesc('id')
            ->offset($offset)
            ->limit($kmess)
            ->get();

        $list = [];
        foreach ($articles as $article) {
            $uploader = $article->uploader_id
                ? '<a href="' . config('johncms')['homeurl'] . '/profile/?user=' . $article->uploader_id . '">' . $this->tools->checkout($article->uploader) . '</a>'
                : $this->tools->checkout($article->uploader);

            $rate = new Rating($article->id);
            $list[] = [
                'id'         => $article->id,
                'name'       => $article->name,
                'announce'   => $this->tools->checkout($article->announce, 0, 0),
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
            'list'       => $list,
            'pagination' => $this->tools->displayPagination('/library/section/' . $id . '?', $offset, $total, $kmess),
        ]);
    }
}
