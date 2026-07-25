<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Downloads\Application\FilePresenter;
use Johncms\Http\PageMeta;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Modules\Downloads\Application\UseCases\ViewFavoritesUseCase;
use Johncms\NavChain;
use Johncms\System\View\Render;
use Johncms\Users\User;
use Symfony\Component\HttpFoundation\Response;

final readonly class FavoritesController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private NavChain $navChain,
        private User $currentUser,
        private ViewFavoritesUseCase $useCase,
        private FilePresenter $filePresenter,
        private PaginationFactory $paginationFactory,
        private PaginationGuard $paginationGuard,
    ) {
        $this->controllerContext->initModule('downloads');
    }

    public function __invoke(): Response
    {
        if (! $this->currentUser->isValid()) {
            return new Response(
                $this->render->render(
                    'system::pages/result',
                    [
                        'title'   => __('Downloads'),
                        'type'    => 'alert-danger',
                        'message' => __('For registered users only'),
                    ]
                ),
                Response::HTTP_FORBIDDEN
            );
        }

        $pagination = $this->paginationFactory->create($this->useCase->count($this->currentUser->id));

        $redirectUrl = $this->paginationGuard->redirectUrl($pagination);
        if ($redirectUrl !== null) {
            redirect($redirectUrl);
        }

        $result = $this->useCase->getPage($this->currentUser->id, $pagination->getPerPage(), $pagination->getOffset());

        $files = [];
        foreach ($result->files as $file) {
            $files[] = $this->filePresenter->present($file);
        }

        $pageTitle = __('Favorites');
        $documentTitle = $pageTitle . ' — ' . __('Downloads');

        $this->navChain->add(__('Downloads'), '/downloads/');
        $this->navChain->add($pageTitle);

        $meta = new PageMeta($documentTitle, $pagination->getCurrentPage());
        $this->render->addData([
            'title'       => $meta->title,
            'page_title'  => $pageTitle,
            'description' => $meta->description,
        ]);

        return new Response($this->render->render(
            'downloads::bookmarks',
            [
                'files'       => $files,
                'total_files' => $pagination->getTotal(),
                'pagination'  => $pagination->render(),
                'urls'        => ['downloads' => '/downloads/'],
            ]
        ));
    }
}
