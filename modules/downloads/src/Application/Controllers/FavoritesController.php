<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\Controllers;

use Downloads\Download;
use Johncms\Http\Controller\ControllerContext;
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
            $files[] = Download::displayFile($file->toArray());
        }

        $pageTitle = __('Favorites');
        $documentTitle = $pageTitle . ' — ' . __('Downloads');

        $this->navChain->add(__('Downloads'), '/downloads/');
        $this->navChain->add($pageTitle);

        $this->render->addData([
            'title'       => $this->buildDocumentTitle($documentTitle, $page),
            'page_title'  => $pageTitle,
            'description' => $this->buildDescription($documentTitle, $page),
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

    private function buildDocumentTitle(string $title, int $page): string
    {
        if ($page <= 1) {
            return $title;
        }

        return $title . ' — ' . d__('system', 'Page') . ' ' . $page;
    }

    private function buildDescription(string $description, int $page): string
    {
        if ($page <= 1 || $description === '') {
            return $description;
        }

        return $description . ' — ' . d__('system', 'Page') . ' ' . $page;
    }
}
