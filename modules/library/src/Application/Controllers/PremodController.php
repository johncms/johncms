<?php

declare(strict_types=1);

namespace Johncms\Modules\Library\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Modules\Library\Domain\Models\LibraryText;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\System\View\Render;
use Johncms\Users\User;
use Johncms\Utils\DateFormatterInterface;
use Johncms\Utils\PlainTextFormatter;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

final readonly class PremodController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private NavChain $navChain,
        private Request $request,
        private Session $session,
        private DateFormatterInterface $dateFormatter,
        private User $currentUser,
        private PaginationFactory $paginationFactory,
        private PaginationGuard $paginationGuard,
    ) {
        $this->controllerContext->initModule('library');
    }

    public function __invoke(): Response
    {
        if (! ($this->currentUser->rights > 4)) {
            return new Response(
                $this->render->render('system::pages/result', [
                    'title'   => __('Moderation Articles'),
                    'type'    => 'alert-danger',
                    'message' => __('Access forbidden'),
                ]),
                Response::HTTP_FORBIDDEN
            );
        }

        $this->navChain->add(__('Library'), '/library/');
        $this->navChain->add(__('Moderation Articles'));

        $this->render->addData([
            'title'      => __('Moderation Articles'),
            'page_title' => __('Moderation Articles'),
        ]);

        $approveId = $this->request->queryInt('approve', 0);

        if ($approveId > 0) {
            $article = LibraryText::query()->find($approveId);
            if ($article !== null) {
                $article->update(['premod' => 1]);
                $this->session->flash('premod_approved_name', $article->name);
            }
            return new RedirectResponse('/library/premod');
        }

        if ($this->request->query->has('approve-all')) {
            LibraryText::query()->where('premod', 0)->update(['premod' => 1]);
            $this->session->flash('premod_approved_all', true);
            return new RedirectResponse('/library/premod');
        }

        $approvedName = $this->session->getFlash('premod_approved_name');
        $approvedAll  = (bool) $this->session->getFlash('premod_approved_all');

        $total = LibraryText::query()->where('premod', 0)->count();

        $pagination = $this->paginationFactory->create($total);
        $redirectUrl = $this->paginationGuard->redirectUrl($pagination);
        if ($redirectUrl !== null) {
            redirect($redirectUrl);
        }

        $articles = LibraryText::query()
            ->select(['id', 'cat_id', 'slug', 'name', 'time', 'uploader', 'uploader_id'])
            ->where('premod', 0)
            ->orderByDesc('time')
            ->offset($pagination->getOffset())
            ->limit($pagination->getPerPage())
            ->get();

        $articleData = [];
        foreach ($articles as $article) {
            $uploader = $article->uploader_id
                ? '<a href="' . config('johncms')['homeurl'] . '/profile/' . $article->uploader_id . '">' . PlainTextFormatter::escape($article->uploader) . '</a>'
                : PlainTextFormatter::escape($article->uploader);
            $articleData[] = [
                'id'   => $article->id,
                'url'  => $article->url,
                'name' => $article->name,
                'who'  => $uploader . ' (' . $this->dateFormatter->format($article->time) . ')',
            ];
        }

        return new Response($this->render->render('library::premod', [
            'approvedName' => $approvedName,
            'approvedAll'  => $approvedAll,
            'total'        => $total,
            'articles'     => $articleData,
            'pagination'   => $pagination->render(),
        ]));
    }
}
