<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Downloads\Application\FilePresenter;
use Johncms\Modules\Downloads\Application\UseCases\ViewTopFilesUseCase;
use Johncms\Modules\Downloads\Domain\Enums\DownloadTopSort;
use Johncms\NavChain;
use Johncms\System\View\Render;
use Johncms\Users\User;

final readonly class TopFilesController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private NavChain $navChain,
        private User $currentUser,
        private ViewTopFilesUseCase $useCase,
        private FilePresenter $filePresenter,
    ) {
        $this->controllerContext->initModule('downloads');
    }

    public function __invoke(string $sort = 'popular'): string
    {
        $downloadSort = $sort === 'popular' ? DownloadTopSort::Popular : DownloadTopSort::fromSlug($sort);
        $config = config('johncms');
        $commentsEnabled = ! empty($config['mod_down_comm']) || $this->currentUser->rights >= 7;

        $result = $this->useCase->execute($downloadSort, $commentsEnabled);

        $files = [];
        foreach ($result->files as $file) {
            $files[] = $this->filePresenter->present($file);
        }

        $pageTitle = match ($downloadSort) {
            DownloadTopSort::Commented  => __('Most Commented'),
            DownloadTopSort::Downloaded => __('Most Downloaded'),
            DownloadTopSort::Popular    => __('Popular Files'),
        };

        $this->navChain->add(__('Downloads'), '/downloads/');
        $this->navChain->add($pageTitle);

        $this->render->addData(
            [
                'title'       => $pageTitle . ' — ' . __('Downloads'),
                'page_title'  => $pageTitle,
                'description' => $pageTitle . ' — ' . __('Downloads'),
            ]
        );

        return $this->render->render(
            'downloads::top',
            [
                'files'   => $files,
                'buttons' => $result->buttons,
                'urls'    => ['downloads' => '/downloads/'],
            ]
        );
    }
}
