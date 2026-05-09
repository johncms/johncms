<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\UseCases;

use Johncms\Modules\Downloads\Application\DTO\CommentsReviewResultDTO;
use Johncms\Modules\Downloads\Domain\Repository\DownloadFileRepositoryInterface;

final readonly class ViewCommentsReviewUseCase
{
    public function __construct(
        private DownloadFileRepositoryInterface $fileRepository,
    ) {
    }

    public function execute(int $page, int $perPage): CommentsReviewResultDTO
    {
        $comments = $this->fileRepository->paginateCommentsReview($page, $perPage);

        return new CommentsReviewResultDTO($comments);
    }
}
