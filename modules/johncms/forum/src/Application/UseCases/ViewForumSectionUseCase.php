<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\CurrentUser;
use Johncms\Modules\Forum\Application\DTO\ForumSectionPageResultDTO;
use Johncms\Modules\Forum\Application\Exceptions\ForumNotFoundException;
use Johncms\Modules\Forum\Application\Services\ForumPermissions;
use Johncms\Modules\Forum\Application\Services\ForumSectionPathService;
use Johncms\Modules\Forum\Domain\Repository\ForumSectionRepositoryInterface;
use Johncms\Modules\Forum\Domain\Repository\ForumTopicRepositoryInterface;
use Johncms\Modules\Forum\Domain\Repository\ForumWhoRepositoryInterface;

final readonly class ViewForumSectionUseCase
{
    public function __construct(
        private AccessCheckerInterface $accessChecker,
        private ForumSectionRepositoryInterface $sectionRepository,
        private ForumTopicRepositoryInterface $topicRepository,
        private ForumWhoRepositoryInterface $whoRepository,
        private ForumSectionPathService $sectionPathService,
        private CurrentUser $currentUser,
    ) {
    }

    /**
     * @throws ForumNotFoundException
     */
    public function execute(string $sectionPath, int $page): ForumSectionPageResultDTO
    {
        $forumSettings = config('forum')['settings'];
        $section = $this->sectionPathService->findSectionByPath($sectionPath);
        if ($section === null) {
            throw new ForumNotFoundException('Section path not found.');
        }

        if ($section->section_type === 1) {
            $currentSection = $forumSettings['file_counters']
                ? $this->sectionRepository->findWithSectionFilesCountById($section->id)
                : $this->sectionRepository->findById($section->id);

            if ($currentSection === null) {
                throw new ForumNotFoundException('Section not found.');
            }

            $perPage = (int) $this->currentUser->user()->config->kmess;
            $total = $this->topicRepository->countReadBySectionId($currentSection->id);
            $topics = $this->topicRepository->getReadBySectionId(
                $currentSection->id,
                $perPage,
                ($page - 1) * $perPage
            );
            $canonical = config('johncms')['homeurl'] . $currentSection->url;
            if ($page > 1) {
                $canonical .= '?page=' . $page;
            }

            return new ForumSectionPageResultDTO(
                section: $currentSection,
                template: '@forum/public/topics.twig',
                viewData: [
                    'id'            => $currentSection->id,
                    'create_access' => $this->canCreateTopic(),
                    'new_topic_url' => '/forum/new-topic/' . $currentSection->id . '/',
                    'files_url'     => '/forum/files/?s=' . $currentSection->id,
                    'topics'        => $topics,
                    'total'         => $total,
                ],
                filesCount: (int) ($currentSection->section_files_count ?? 0),
                onlineUsers: $this->whoRepository->countForumUsers(),
                onlineGuests: $this->whoRepository->countForumGuests(),
                canonical: $canonical,
            );
        }

        $currentSection = $forumSettings['file_counters']
            ? $this->sectionRepository->findWithCategoryFilesCountById($section->id)
            : $this->sectionRepository->findById($section->id);

        if ($currentSection === null) {
            throw new ForumNotFoundException('Category not found.');
        }

        $children = $this->sectionRepository->getChildrenWithCounts($currentSection->id);

        return new ForumSectionPageResultDTO(
            section: $currentSection,
            template: '@forum/public/section.twig',
            viewData: [
                'id'        => $currentSection->id,
                'files_url' => '/forum/files/?c=' . $currentSection->id,
                'sections'  => $children,
                'total'     => $children->count(),
            ],
            filesCount: (int) ($currentSection->category_files_count ?? 0),
            onlineUsers: $this->whoRepository->countForumUsers(),
            onlineGuests: $this->whoRepository->countForumGuests(),
            canonical: config('johncms')['homeurl'] . $currentSection->url,
        );
    }

    private function canCreateTopic(): bool
    {
        return $this->currentUser->isValid()
            && ! isset($this->currentUser->user()->ban['1'])
            && ! isset($this->currentUser->user()->ban['11'])
            && $this->accessChecker->allows(ForumPermissions::POST);
    }
}
