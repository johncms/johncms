<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Downloads\Application\FilePresenter;
use Johncms\Http\PageMeta;
use Johncms\Modules\Downloads\Application\UseCases\ViewFavoritesUseCase;
use Johncms\NavChain;
use Johncms\System\Http\Request;
use Johncms\System\Legacy\Tools;
use Johncms\System\View\Render;
use Johncms\Users\User;

final readonly class FavoritesController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private NavChain $navChain,
        private Tools $tools,
        private User $currentUser,
        private ViewFavoritesUseCase $useCase,
        private FilePresenter $filePresenter,
    ) {
        $this->controllerContext->initModule('downloads');
    }

    public function __invoke(): string
    {
        if (! $this->currentUser->isValid()) {
            http_response_code(403);
            return $this->render->render(
                'system::pages/result',
                [
                    'title'   => __('Downloads'),
                    'type'    => 'alert-danger',
                    'message' => __('For registered users only'),
                ]
            );
        }

        $page = max(1, (int) $this->request->getQuery('page', 1));

        $result = $this->useCase->execute($this->currentUser->id, $page, $this->currentUser->config->kmess);

        $files = [];
        foreach ($result->files as $file) {
            $files[] = $this->filePresenter->present($file);
        }

        $pageTitle = __('Favorites');
        $documentTitle = $pageTitle . ' — ' . __('Downloads');

        $this->navChain->add(__('Downloads'), '/downloads/');
        $this->navChain->add($pageTitle);

        $meta = new PageMeta($documentTitle, $page);
        $this->render->addData([
            'title'       => $meta->title,
            'page_title'  => $pageTitle,
            'description' => $meta->description,
        ]);

        return $this->render->render(
            'downloads::bookmarks',
            [
                'files'       => $files,
                'total_files' => $result->files->total(),
                'pagination'  => $this->tools->displayPagination(
                    '/downloads/favorites/?',
                    ($page - 1) * $this->currentUser->config->kmess,
                    $result->files->total(),
                    $this->currentUser->config->kmess
                ),
                'urls'        => ['downloads' => '/downloads/'],
            ]
        );
    }
}
