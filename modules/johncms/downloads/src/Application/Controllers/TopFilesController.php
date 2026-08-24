<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\Controllers;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Modules\Downloads\Application\Services\DownloadsPermissions;
use Johncms\Modules\Downloads\Application\FilePresenter;
use Johncms\Modules\Downloads\Application\UseCases\ViewTopFilesUseCase;
use Johncms\Modules\Downloads\Domain\Enums\DownloadTopSort;
use Johncms\Http\View\ViewResponse;
use Johncms\NavChain;

final readonly class TopFilesController
{
    public function __construct(
        private AccessCheckerInterface $accessChecker,
        private NavChain $navChain,
        private ViewTopFilesUseCase $useCase,
        private FilePresenter $filePresenter,
    ) {
    }

    public function __invoke(string $sort = 'popular'): ViewResponse
    {
        $downloadSort = $sort === 'popular' ? DownloadTopSort::Popular : DownloadTopSort::fromSlug($sort);
        $config = config('johncms');
        $commentsEnabled = ! empty($config['mod_down_comm'])
            || $this->accessChecker->allows(DownloadsPermissions::COMMENTS_ALWAYS_VIEW);

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

        return new ViewResponse(
            '@downloads/public/top-files.twig',
            [
                'title'       => $pageTitle . ' — ' . __('Downloads'),
                'page_title'  => $pageTitle,
                'description' => $pageTitle . ' — ' . __('Downloads'),
                'files'       => $files,
                'buttons'     => $result->buttons,
                'urls'        => ['downloads' => '/downloads/'],
            ]
        );
    }
}
