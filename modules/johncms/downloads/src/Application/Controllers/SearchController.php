<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\Controllers;

use Johncms\Modules\Downloads\Application\FilePresenter;
use Johncms\Http\PageMeta;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Modules\Downloads\Application\UseCases\SearchFilesUseCase;
use Johncms\Http\View\ViewResponse;
use Johncms\NavChain;
use Johncms\Http\Request;

final readonly class SearchController
{
    public function __construct(
        private NavChain $navChain,
        private SearchFilesUseCase $useCase,
        private FilePresenter $filePresenter,
        private PaginationFactory $paginationFactory,
        private PaginationGuard $paginationGuard,
    ) {
    }

    public function __invoke(Request $request): ViewResponse
    {
        $rawQuery = trim($request->queryParam('search', ''));
        $searchInDescription = (bool) $request->queryInt('id', 0);

        $this->navChain->add(__('Downloads'), '/downloads/');
        $this->navChain->add(__('Search'), '/downloads/search/');

        $length = mb_strlen($rawQuery);

        // Nothing searched yet, or a query the search does not take: the form alone, with the
        // reason beside it in the second case.
        if ($length < 2 || $length > 64) {
            $pageTitle = __('Search');
            $documentTitle = $pageTitle . ' — ' . __('Downloads');

            return new ViewResponse(
                '@downloads/public/search.twig',
                [
                    'title'                 => $documentTitle,
                    'page_title'            => $pageTitle,
                    'description'           => $documentTitle,
                    'files'                 => [],
                    'total'                 => 0,
                    'pagination'            => null,
                    'search_query'          => $rawQuery,
                    'search_in_description' => $searchInDescription,
                    'searched'              => false,
                    'error'                 => $length > 0 ? __('The query has to be from 2 to 64 characters long.') : '',
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

        $files = [];
        foreach ($result->files as $file) {
            $files[] = $this->filePresenter->present($file);
        }

        return new ViewResponse(
            '@downloads/public/search.twig',
            [
                'title'                 => $meta->title,
                'page_title'            => $pageTitle,
                'description'           => $meta->description,
                'files'                 => $files,
                'total'                 => $pagination->getTotal(),
                'pagination'            => $pagination->hasPages() ? $pagination->render(withRange: true) : null,
                'search_query'          => $result->searchQuery,
                'search_in_description' => $result->searchInDescription,
                'searched'              => true,
                'error'                 => '',
            ]
        );
    }
}
