<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Modules\Forum\Application\DTO\ForumIndexResultDTO;
use Johncms\Modules\Forum\Domain\Repository\ForumFileRepositoryInterface;
use Johncms\Modules\Forum\Domain\Repository\ForumSectionRepositoryInterface;
use Johncms\Modules\Forum\Domain\Repository\ForumWhoRepositoryInterface;

final readonly class ViewForumIndexUseCase
{
    public function __construct(
        private ForumSectionRepositoryInterface $sectionRepository,
        private ForumWhoRepositoryInterface $whoRepository,
        private ForumFileRepositoryInterface $fileRepository,
    ) {
    }

    public function execute(): ForumIndexResultDTO
    {
        $settings = config('forum')['settings'];
        $showFileCounters = (bool) $settings['file_counters'];

        return new ForumIndexResultDTO(
            sections: $this->sectionRepository->getRootSectionsWithSubsections(),
            onlineUsers: $this->whoRepository->countForumUsers(),
            onlineGuests: $this->whoRepository->countForumGuests(),
            filesCount: $showFileCounters ? $this->fileRepository->countAll() : 0,
            showFileCounters: $showFileCounters,
            keywords: (string) $settings['forum_keywords'],
            description: (string) $settings['forum_description'],
        );
    }
}
