<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\UseCases;

use Johncms\Modules\Downloads\Application\DTO\TopFilesResultDTO;
use Johncms\Modules\Downloads\Domain\Enums\DownloadTopSort;
use Johncms\Modules\Downloads\Domain\Repository\DownloadFileRepositoryInterface;

final readonly class ViewTopFilesUseCase
{
    private const TOP_LIMIT = 25;

    public function __construct(
        private DownloadFileRepositoryInterface $fileRepository,
    ) {
    }

    public function execute(DownloadTopSort $sort, bool $commentsEnabled): TopFilesResultDTO
    {
        $files = $this->fileRepository->getTopFiles($sort, self::TOP_LIMIT);

        $buttons = [
            'pop' => [
                'name'   => __('Popular Files'),
                'url'    => '/downloads/top/',
                'active' => $sort === DownloadTopSort::Popular,
            ],
            'most_downloaded' => [
                'name'   => __('Most Downloaded'),
                'url'    => '/downloads/top/downloaded/',
                'active' => $sort === DownloadTopSort::Downloaded,
            ]
        ];

        if ($commentsEnabled) {
            $buttons['comments'] = [
                'name'   => __('Most Commented'),
                'url'    => '/downloads/top/commented/',
                'active' => $sort === DownloadTopSort::Commented,
            ];
        }

        return new TopFilesResultDTO($files, $sort, $buttons);
    }
}
