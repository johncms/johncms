<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Downloads\Application\FilePresenter;
use Johncms\Http\PageMeta;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Modules\Downloads\Application\Exceptions\DownloadNotFoundException;
use Johncms\Modules\Downloads\Application\UseCases\ViewNewFilesUseCase;
use Johncms\Http\View\ViewResponse;
use Johncms\NavChain;
use Johncms\Http\Request;

final readonly class NewFilesController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private NavChain $navChain,
        private ViewNewFilesUseCase $useCase,
        private FilePresenter $filePresenter,
        private PaginationFactory $paginationFactory,
        private PaginationGuard $paginationGuard,
    ) {
        $this->controllerContext->initModule('downloads');
    }

    public function __invoke(Request $request): ViewResponse
    {
        $categoryId = max(0, $request->queryInt('id', 0));

        try {
            $pagination = $this->paginationFactory->create($this->useCase->count($categoryId));

            $redirectUrl = $this->paginationGuard->redirectUrl($pagination);
            if ($redirectUrl !== null) {
                redirect($redirectUrl);
            }

            $result = $this->useCase->getPage($pagination->getPerPage(), $pagination->getOffset(), $categoryId);
        } catch (DownloadNotFoundException) {
            return new ViewResponse(
                '@theme/pages/result.twig',
                [
                    'title'         => __('New Files'),
                    'type'          => 'alert-danger',
                    'message'       => __('The directory does not exist'),
                    'back_url'      => '/downloads/',
                    'back_url_name' => __('Downloads'),
                ]
            );
        }

        $files = [];
        foreach ($result->files as $file) {
            $files[] = $this->filePresenter->present($file);
        }

        $this->navChain->add(__('Downloads'), '/downloads/');
        $this->navChain->add(__('New Files'));

        $pageTitle = __('New Files');
        $documentTitle = $pageTitle . ' — ' . __('Downloads');

        $meta = new PageMeta($documentTitle, $pagination->getCurrentPage());

        return new ViewResponse(
            '@downloads/public/new-files.twig',
            [
                'title'       => $meta->title,
                'page_title'  => $pageTitle,
                'description' => $meta->description,
                'pagination'  => $pagination->hasPages() ? $pagination->render() : null,
                'files'       => $files,
                'total'       => $pagination->getTotal(),
                'urls'        => ['downloads' => '/downloads/'],
            ]
        );
    }
}
