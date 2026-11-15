<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Modules\Forum\Application\Services\ForumSectionRows;
use Johncms\Modules\Forum\Application\DTO\ForumIndexResultDTO;
use Johncms\Modules\Forum\Domain\Repository\ForumFileRepositoryInterface;
use Johncms\Modules\Forum\Domain\Repository\ForumWhoRepositoryInterface;

final readonly class ViewForumIndexUseCase
{
    public function __construct(
        private ForumSectionRows $sectionRows,
        private ForumWhoRepositoryInterface $whoRepository,
        private ForumFileRepositoryInterface $fileRepository,
    ) {
    }

    public function execute(): ForumIndexResultDTO
    {
        $settings = config('forum')['settings'];
        $showFileCounters = (bool) $settings['file_counters'];

        $sections = $this->sectionRows->under(0);

        return new ForumIndexResultDTO(
            sections: $sections,
            totals: $this->sectionRows->totals($sections),
            onlineUsers: $this->whoRepository->countForumUsers(),
            onlineGuests: $this->whoRepository->countForumGuests(),
            filesCount: $showFileCounters ? $this->fileRepository->countAll() : 0,
            showFileCounters: $showFileCounters,
            keywords: (string) $settings['forum_keywords'],
            description: (string) $settings['forum_description'],
        );
    }
}
