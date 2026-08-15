<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\Controllers;

use Johncms\Auth\CurrentUser;
use Johncms\Modules\Downloads\Application\FilePresenter;
use Johncms\Http\PageMeta;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Modules\Downloads\Application\UseCases\ViewFavoritesUseCase;
use Johncms\Http\View\ViewResponse;
use Johncms\NavChain;
use Symfony\Component\HttpFoundation\Response;

final readonly class FavoritesController
{
    public function __construct(
        private NavChain $navChain,
        private CurrentUser $currentUser,
        private ViewFavoritesUseCase $useCase,
        private FilePresenter $filePresenter,
        private PaginationFactory $paginationFactory,
        private PaginationGuard $paginationGuard,
    ) {
    }

    public function __invoke(): ViewResponse
    {
        if (! $this->currentUser->isValid()) {
            return new ViewResponse(
                '@theme/pages/result.twig',
                [
                    'title'   => __('Downloads'),
                    'type'    => 'alert-danger',
                    'message' => __('For registered users only'),
                ],
                Response::HTTP_FORBIDDEN
            );
        }

        $pagination = $this->paginationFactory->create($this->useCase->count($this->currentUser->id()));

        $redirectUrl = $this->paginationGuard->redirectUrl($pagination);
        if ($redirectUrl !== null) {
            redirect($redirectUrl);
        }

        $result = $this->useCase->getPage($this->currentUser->id(), $pagination->getPerPage(), $pagination->getOffset());

        $files = [];
        foreach ($result->files as $file) {
            $files[] = $this->filePresenter->present($file);
        }

        $pageTitle = __('Favorites');
        $documentTitle = $pageTitle . ' — ' . __('Downloads');

        $this->navChain->add(__('Downloads'), '/downloads/');
        $this->navChain->add($pageTitle);

        $meta = new PageMeta($documentTitle, $pagination->getCurrentPage());

        return new ViewResponse(
            '@downloads/public/favorites.twig',
            [
                'title'       => $meta->title,
                'page_title'  => $pageTitle,
                'description' => $meta->description,
                'files'       => $files,
                'total_files' => $pagination->getTotal(),
                'pagination'  => $pagination->hasPages() ? $pagination->render() : null,
                'urls'        => ['downloads' => '/downloads/'],
            ]
        );
    }
}
