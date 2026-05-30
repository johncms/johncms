<?php

declare(strict_types=1);

namespace Johncms\Modules\Library\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Library\Domain\Models\LibraryText;
use Johncms\NavChain;
use Johncms\System\Http\Request;
use Johncms\System\Http\Session;
use Johncms\System\Legacy\Tools;
use Johncms\System\View\Render;
use Johncms\Users\User;

final readonly class PremodController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private NavChain $navChain,
        private Request $request,
        private Session $session,
        private Tools $tools,
        private User $currentUser,
    ) {
        $this->controllerContext->initModule('library');
    }

    public function __invoke(): string
    {
        if (! ($this->currentUser->rights > 4)) {
            http_response_code(403);
            return $this->render->render('system::pages/result', [
                'title'   => __('Moderation Articles'),
                'type'    => 'alert-danger',
                'message' => __('Access forbidden'),
            ]);
        }

        $this->navChain->add(__('Library'), '/library/');
        $this->navChain->add(__('Moderation Articles'));

        $this->render->addData([
            'title'      => __('Moderation Articles'),
            'page_title' => __('Moderation Articles'),
        ]);

        $approveId = (int) $this->request->getQuery('approve', 0);

        if ($approveId > 0) {
            $article = LibraryText::query()->find($approveId);
            if ($article !== null) {
                $article->update(['premod' => 1]);
                $this->session->flash('premod_approved_name', $article->name);
            }
            header('Location: /library/premod', true, 302);
            exit;
        }

        if ($this->request->getQuery('approve-all') !== null) {
            LibraryText::query()->where('premod', 0)->update(['premod' => 1]);
            $this->session->flash('premod_approved_all', true);
            header('Location: /library/premod', true, 302);
            exit;
        }

        $approvedName = $this->session->getFlash('premod_approved_name');
        $approvedAll  = (bool) $this->session->getFlash('premod_approved_all');

        $page  = max(1, (int) $this->request->getQuery('page', 1));
        $kmess = $this->currentUser->config->kmess;
        $total = LibraryText::query()->where('premod', 0)->count();
        $page  = min($page, (int) ceil($total / $kmess) ?: 1);
        $offset = ($page - 1) * $kmess;

        $articles = LibraryText::query()
            ->select(['id', 'cat_id', 'slug', 'name', 'time', 'uploader', 'uploader_id'])
            ->where('premod', 0)
            ->orderByDesc('time')
            ->offset($offset)
            ->limit($kmess)
            ->get();

        $articleData = [];
        foreach ($articles as $article) {
            $uploader = $article->uploader_id
                ? '<a href="' . config('johncms')['homeurl'] . '/profile/' . $article->uploader_id . '">' . $this->tools->checkout($article->uploader) . '</a>'
                : $this->tools->checkout($article->uploader);
            $articleData[] = [
                'id'   => $article->id,
                'url'  => $article->url,
                'name' => $article->name,
                'who'  => $uploader . ' (' . $this->tools->displayDate($article->time) . ')',
            ];
        }

        return $this->render->render('library::premod', [
            'approvedName' => $approvedName,
            'approvedAll'  => $approvedAll,
            'total'        => $total,
            'articles'     => $articleData,
            'pagination'   => $this->tools->displayPagination('/library/premod?', $offset, $total, $kmess),
        ]);
    }
}
