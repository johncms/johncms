<?php

declare(strict_types=1);

namespace Johncms\Modules\Library\Application\Controllers;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\Http\View\ViewResponse;
use Johncms\Modules\Library\Application\Services\LibraryPermissions;
use Johncms\Modules\Library\Domain\Models\LibraryText;
use Johncms\NavChain;
use Johncms\Utils\DateFormatterInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

final readonly class PremodController
{
    public function __construct(
        private AccessCheckerInterface $accessChecker,
        private NavChain $navChain,
        private Session $session,
        private DateFormatterInterface $dateFormatter,
        private PaginationFactory $paginationFactory,
        private PaginationGuard $paginationGuard,
    ) {
    }

    public function __invoke(Request $request): Response|ViewResponse
    {
        if (! $this->accessChecker->allows(LibraryPermissions::MODERATE)) {
            return new ViewResponse(
                '@theme/pages/result.twig',
                [
                    'title'   => __('Moderation Articles'),
                    'type'    => 'alert-danger',
                    'message' => __('Access forbidden'),
                ],
                Response::HTTP_FORBIDDEN
            );
        }

        $this->navChain->add(__('Library'), '/library/');
        $this->navChain->add(__('Moderation Articles'));

        $approveId = $request->queryInt('approve', 0);

        if ($approveId > 0) {
            $article = LibraryText::query()->find($approveId);
            if ($article !== null) {
                $article->update(['premod' => 1]);
                $this->session->flash('premod_approved_name', $article->name);
            }
            return new RedirectResponse('/library/premod');
        }

        if ($request->query->has('approve-all')) {
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
            $articleData[] = [
                'id'          => $article->id,
                'url'         => $article->url,
                'name'        => $article->name,
                'uploader_id' => $article->uploader_id,
                'uploader'    => $article->uploader,
                'date'        => $this->dateFormatter->format($article->time),
            ];
        }

        return new ViewResponse('@library/public/premod.twig', [
            'title'         => __('Moderation Articles'),
            'page_title'    => __('Moderation Articles'),
            'approved_name' => $approvedName,
            'approved_all'  => $approvedAll,
            'total'         => $total,
            'articles'      => $articleData,
            'pagination'    => $pagination->hasPages() ? $pagination->render() : null,
        ]);
    }
}
