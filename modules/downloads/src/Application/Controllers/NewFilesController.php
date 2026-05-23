<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Downloads\Application\FilePresenter;
use Johncms\Http\PageMeta;
use Johncms\Modules\Downloads\Application\Exceptions\DownloadNotFoundException;
use Johncms\Modules\Downloads\Application\UseCases\ViewNewFilesUseCase;
use Johncms\NavChain;
use Johncms\System\Http\Request;
use Johncms\System\Legacy\Tools;
use Johncms\System\View\Render;
use Johncms\Users\User;

final readonly class NewFilesController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private NavChain $navChain,
        private Tools $tools,
        private User $currentUser,
        private ViewNewFilesUseCase $useCase,
        private FilePresenter $filePresenter,
    ) {
        $this->controllerContext->initModule('downloads');
    }

    public function __invoke(): string
    {
        $categoryId = max(0, (int) $this->request->getQuery('id', 0));
        $page = max(1, (int) $this->request->getQuery('page', 1));

        try {
            $result = $this->useCase->execute($page, $this->currentUser->config->kmess, $categoryId);
        } catch (DownloadNotFoundException) {
            return $this->render->render(
                'system::pages/result',
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
        $paginationBase = '/downloads/new/?' . ($categoryId ? 'id=' . $categoryId . '&amp;' : '');

        $meta = new PageMeta($documentTitle, $page);
        $this->render->addData(
            [
                'title'       => $meta->title,
                'page_title'  => $pageTitle,
                'description' => $meta->description,
            ]
        );

        return $this->render->render(
            'downloads::new_files',
            [
                'pagination' => $this->tools->displayPagination(
                    $paginationBase,
                    ($page - 1) * $this->currentUser->config->kmess,
                    $result->files->total(),
                    $this->currentUser->config->kmess
                ),
                'files'      => $files,
                'total'      => $result->files->total(),
                'urls'       => ['downloads' => '/downloads/'],
            ]
        );
    }
}
