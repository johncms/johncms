<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Downloads\Application\FilePresenter;
use Johncms\Http\PageMeta;
use Johncms\Modules\Downloads\Application\UseCases\SearchFilesUseCase;
use Johncms\NavChain;
use Johncms\System\Http\Request;
use Johncms\System\Legacy\Tools;
use Johncms\System\View\Render;
use Johncms\Users\User;

final readonly class SearchController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private NavChain $navChain,
        private Tools $tools,
        private User $currentUser,
        private SearchFilesUseCase $useCase,
        private FilePresenter $filePresenter,
    ) {
        $this->controllerContext->initModule('downloads');
    }

    public function __invoke(): string
    {
        $rawQuery = trim((string) $this->request->getQuery('search', ''));
        $searchInDescription = (bool) $this->request->getQuery('id', 0);

        $page = max(1, (int) $this->request->getQuery('page', 1));

        $this->navChain->add(__('Downloads'), '/downloads/');
        $this->navChain->add(__('Search'), '/downloads/search/');

        if (! empty($rawQuery) && mb_strlen($rawQuery) < 2 || mb_strlen($rawQuery) > 64) {
            return $this->render->render(
                'system::pages/result',
                [
                    'title'         => __('Error'),
                    'type'          => 'alert-danger',
                    'message'       => __('Invalid file name length. Allowed a minimum of 3 and a maximum of 64 characters.'),
                    'back_url'      => '/downloads/',
                    'back_url_name' => __('Downloads'),
                ]
            );
        }

        if (empty($rawQuery)) {
            $pageTitle = __('Search');
            $documentTitle = $pageTitle . ' — ' . __('Downloads');
            $this->render->addData(
                [
                    'title'       => $documentTitle,
                    'page_title'  => $pageTitle,
                    'description' => $documentTitle,
                ]
            );

            return $this->render->render(
                'downloads::search',
                [
                    'files'                 => [],
                    'total'                 => 0,
                    'pagination'            => '',
                    'search_query'          => '',
                    'search_in_description' => false,
                    'show_empty_info'       => false,
                    'urls'                  => ['downloads' => '/downloads/'],
                ]
            );
        }

        $result = $this->useCase->execute($rawQuery, $searchInDescription, $page, $this->currentUser->config->kmess);

        $pageTitle = __('Search results for: %s', $result->searchQuery);
        $documentTitle = $pageTitle . ' — ' . __('Downloads');

        $meta = new PageMeta($documentTitle, $page);
        $this->render->addData(
            [
                'title'       => $meta->title,
                'page_title'  => $pageTitle,
                'description' => $meta->description,
            ]
        );

        $files = [];
        foreach ($result->files as $file) {
            $files[] = $this->filePresenter->present($file);
        }

        $paginationParams = http_build_query(['search' => $result->searchQuery, 'id' => $searchInDescription ? 1 : 0]);

        return $this->render->render(
            'downloads::search',
            [
                'files'                 => $files,
                'total'                 => $result->files->total(),
                'pagination'            => $this->tools->displayPagination(
                    '/downloads/search/?' . $paginationParams . '&amp;',
                    ($page - 1) * $this->currentUser->config->kmess,
                    $result->files->total(),
                    $this->currentUser->config->kmess
                ),
                'search_query'          => htmlspecialchars($result->searchQuery),
                'search_in_description' => $result->searchInDescription,
                'show_empty_info'       => true,
                'urls'                  => ['downloads' => '/downloads/'],
            ]
        );
    }
}
