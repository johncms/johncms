<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Downloads\Application\FilePresenter;
use Johncms\Http\PageMeta;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Modules\Downloads\Application\UseCases\SearchFilesUseCase;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\System\View\Render;

final readonly class SearchController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private NavChain $navChain,
        private SearchFilesUseCase $useCase,
        private FilePresenter $filePresenter,
        private PaginationFactory $paginationFactory,
        private PaginationGuard $paginationGuard,
    ) {
        $this->controllerContext->initModule('downloads');
    }

    public function __invoke(Request $request): string
    {
        $rawQuery = trim($request->queryParam('search', ''));
        $searchInDescription = (bool) $request->queryInt('id', 0);

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

        $pagination = $this->paginationFactory->create($this->useCase->count($rawQuery, $searchInDescription));

        $redirectUrl = $this->paginationGuard->redirectUrl($pagination);
        if ($redirectUrl !== null) {
            redirect($redirectUrl);
        }

        $result = $this->useCase->getPage($rawQuery, $searchInDescription, $pagination->getPerPage(), $pagination->getOffset());

        $pageTitle = __('Search results for: %s', $result->searchQuery);
        $documentTitle = $pageTitle . ' — ' . __('Downloads');

        $meta = new PageMeta($documentTitle, $pagination->getCurrentPage());
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

        return $this->render->render(
            'downloads::search',
            [
                'files'                 => $files,
                'total'                 => $pagination->getTotal(),
                'pagination'            => $pagination->render(),
                'search_query'          => htmlspecialchars($result->searchQuery),
                'search_in_description' => $result->searchInDescription,
                'show_empty_info'       => true,
                'urls'                  => ['downloads' => '/downloads/'],
            ]
        );
    }
}
