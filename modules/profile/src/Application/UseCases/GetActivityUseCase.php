<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\UseCases;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Johncms\Modules\Forum\Application\Services\ForumTopicPathService;
use Johncms\Modules\Forum\Domain\Models\ForumMessage;
use Johncms\Modules\Forum\Domain\Models\ForumTopic;
use Johncms\Modules\Guestbook\Domain\Models\GuestbookEntry;
use Johncms\Modules\Profile\Application\DTO\ActivityDTO;
use Johncms\Modules\Profile\Application\Exceptions\ProfileNotFoundException;
use Johncms\Modules\Profile\Application\Services\ForumActivityPreviewService;
use Johncms\Modules\Profile\Domain\Enums\ActivityType;
use Johncms\Modules\Profile\Domain\Repository\ProfileActivityRepositoryInterface;
use Johncms\Modules\Profile\Domain\Repository\ProfileUserRepositoryInterface;
use Johncms\System\Legacy\Tools;
use Johncms\Users\User;

final readonly class GetActivityUseCase
{
    public function __construct(
        private ProfileUserRepositoryInterface $profileUserRepository,
        private ProfileActivityRepositoryInterface $activityRepository,
        private ForumActivityPreviewService $forumPreview,
        private ForumTopicPathService $topicPathService,
        private Tools $tools,
        private User $currentUser,
    ) {
    }

    public function execute(int $userId, ActivityType $type, int $perPage): ActivityDTO
    {
        $profileUser = $this->profileUserRepository->findById($userId);

        // Hide non-confirmed profiles from regular users (only admins with rights >= 7 may see them)
        if ($profileUser === null || (! $profileUser->preg && $this->currentUser->rights < 7)) {
            throw new ProfileNotFoundException();
        }

        $includeDeleted = $this->currentUser->rights >= 7;

        $paginator = match ($type) {
            ActivityType::Comments => $this->activityRepository->paginateGuestbookEntries(
                $profileUser->id,
                $this->currentUser->rights >= 1,
                $perPage
            ),
            ActivityType::Topics => $this->activityRepository->paginateForumTopics($profileUser->id, $includeDeleted, $perPage),
            ActivityType::Messages => $this->activityRepository->paginateForumMessages($profileUser->id, $includeDeleted, $perPage),
        };

        $items = match ($type) {
            ActivityType::Comments => $this->mapComments($paginator),
            ActivityType::Topics   => $this->mapTopics($paginator, $includeDeleted),
            ActivityType::Messages => $this->mapMessages($paginator),
        };

        return new ActivityDTO(
            itemType: $type->itemType(),
            items: $items,
            total: $paginator->total(),
            pagination: $paginator->render(),
            profileName: $profileUser->name,
            profileId: $profileUser->id,
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function mapMessages(LengthAwarePaginator $paginator): array
    {
        $rows = [];
        foreach ($paginator->items() as $message) {
            if (! $message instanceof ForumMessage) {
                continue;
            }
            $topic = $message->topic;
            $section = $topic?->section;
            $category = $section?->parentSection;

            $rows[] = [
                'topic_url'     => $this->topicPathService->getTopicUrlById((int) $message->topic_id) ?? '/forum/',
                'topic_name'    => $topic->name ?? '',
                'topic_id'      => $message->topic_id,
                'text'          => $this->forumPreview->make((string) $message->text, (int) $message->rights),
                'message_url'   => '/forum/post/' . $message->id . '/',
                'display_date'  => $this->tools->displayDate($message->date),
                'category_name' => $category->name ?? '',
                'category_url'  => '/forum/?id=' . ($category->id ?? ''),
                'section_name'  => $section->name ?? '',
                'section_url'   => '/forum/?type=topics&id=' . ($section->id ?? ''),
            ];
        }

        return $rows;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function mapTopics(LengthAwarePaginator $paginator, bool $includeDeleted): array
    {
        $rows = [];
        foreach ($paginator->items() as $topic) {
            if (! $topic instanceof ForumTopic) {
                continue;
            }
            $section = $topic->section;
            $category = $section?->parentSection;
            $firstMessage = $this->activityRepository->findFirstTopicMessage((int) $topic->id, $includeDeleted);

            $rows[] = [
                'topic_url'     => $this->topicPathService->getTopicUrlById((int) $topic->id) ?? '/forum/',
                'topic_name'    => $topic->name,
                'topic_id'      => $topic->id,
                'text'          => $this->forumPreview->make(
                    (string) ($firstMessage->text ?? ''),
                    (int) ($firstMessage->rights ?? 0)
                ),
                'display_date'  => $this->tools->displayDate($topic->last_post_date),
                'category_name' => $category->name ?? '',
                'category_url'  => '/forum/?id=' . ($category->id ?? ''),
                'section_name'  => $section->name ?? '',
                'section_url'   => '/forum/?type=topics&id=' . ($section->id ?? ''),
            ];
        }

        return $rows;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function mapComments(LengthAwarePaginator $paginator): array
    {
        $rows = [];
        foreach ($paginator->items() as $entry) {
            if (! $entry instanceof GuestbookEntry) {
                continue;
            }
            $rows[] = [
                'display_date' => $this->tools->displayDate((int) $entry->getRawOriginal('time')),
                'text'         => $entry->post_text,
            ];
        }

        return $rows;
    }
}
