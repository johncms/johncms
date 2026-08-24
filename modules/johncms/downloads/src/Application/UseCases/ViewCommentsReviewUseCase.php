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

    public function count(): int
    {
        return $this->fileRepository->countCommentsReview();
    }

    public function getPage(int $limit, int $offset): CommentsReviewResultDTO
    {
        return new CommentsReviewResultDTO($this->fileRepository->getCommentsReview($limit, $offset));
    }
}
