<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\UseCases;

use Illuminate\Support\Collection;
use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Modules\Forum\Application\Services\ForumPermissions;
use Johncms\Modules\Forum\Application\Services\ForumTopicPathService;
use Johncms\Modules\Forum\Domain\Models\ForumMessage;
use Johncms\Modules\Forum\Domain\Models\ForumTopic;
use Johncms\Modules\Guestbook\Application\Services\GuestbookEntryTextFormatter;
use Johncms\Modules\Guestbook\Application\Services\GuestbookPermissions;
use Johncms\Modules\Guestbook\Domain\Models\GuestbookEntry;
use Johncms\Modules\Profile\Application\DTO\ActivityDTO;
use Johncms\Modules\Profile\Application\Exceptions\ProfileNotFoundException;
use Johncms\Modules\Profile\Application\Services\ForumActivityPreviewService;
use Johncms\Modules\Profile\Application\Services\ProfilePermissions;
use Johncms\Modules\Profile\Domain\Enums\ActivityType;
use Johncms\Modules\Profile\Domain\Repository\ProfileActivityRepositoryInterface;
use Johncms\Modules\Profile\Domain\Repository\ProfileUserRepositoryInterface;
use Johncms\Users\User;
use Johncms\Utils\DateFormatterInterface;

final readonly class GetActivityUseCase
{
    public function __construct(
        private ProfileUserRepositoryInterface $profileUserRepository,
        private ProfileActivityRepositoryInterface $activityRepository,
        private ForumActivityPreviewService $forumPreview,
        private ForumTopicPathService $topicPathService,
        private GuestbookEntryTextFormatter $guestbookTextFormatter,
        private DateFormatterInterface $dateFormatter,
        private AccessCheckerInterface $accessChecker,
    ) {
    }

    public function count(int $userId, ActivityType $type): int
    {
        $profileUser = $this->loadProfile($userId);

        return match ($type) {
            ActivityType::Comments => $this->activityRepository->countGuestbookEntries(
                $profileUser->id,
                $this->includeAdminClub()
            ),
            ActivityType::Topics   => $this->activityRepository->countForumTopics($profileUser->id, $this->includeDeleted()),
            ActivityType::Messages => $this->activityRepository->countForumMessages($profileUser->id, $this->includeDeleted()),
        };
    }

    public function getPage(int $userId, ActivityType $type, int $limit, int $offset): ActivityDTO
    {
        $profileUser = $this->loadProfile($userId);

        $includeDeleted = $this->includeDeleted();

        $records = match ($type) {
            ActivityType::Comments => $this->activityRepository->getGuestbookEntries(
                $profileUser->id,
                $this->includeAdminClub(),
                $limit,
                $offset
            ),
            ActivityType::Topics   => $this->activityRepository->getForumTopics($profileUser->id, $includeDeleted, $limit, $offset),
            ActivityType::Messages => $this->activityRepository->getForumMessages($profileUser->id, $includeDeleted, $limit, $offset),
        };

        $items = match ($type) {
            ActivityType::Comments => $this->mapComments($records),
            ActivityType::Topics   => $this->mapTopics($records, $includeDeleted),
            ActivityType::Messages => $this->mapMessages($records),
        };

        return new ActivityDTO(
            itemType: $type->itemType(),
            items: $items,
            profileName: $profileUser->name,
            profileId: $profileUser->id,
        );
    }

    private function loadProfile(int $userId): User
    {
        $profileUser = $this->profileUserRepository->findById($userId);

        // An account awaiting confirmation exists only for whoever is allowed to see one
        if ($profileUser === null || (! $profileUser->preg && ! $this->accessChecker->allows(ProfilePermissions::UNCONFIRMED_VIEW))) {
            throw new ProfileNotFoundException();
        }

        return $profileUser;
    }

    private function includeDeleted(): bool
    {
        return $this->accessChecker->allows(ForumPermissions::DELETED_VIEW);
    }

    /**
     * Whether the entries of the admin club count as activity. The guestbook decides who may
     * enter the club, so the question is asked with its permission.
     */
    private function includeAdminClub(): bool
    {
        return $this->accessChecker->allows(GuestbookPermissions::ADMIN_CLUB_VIEW);
    }

    /**
     * @param Collection<int, ForumMessage> $records
     * @return array<int, array<string, mixed>>
     */
    private function mapMessages(Collection $records): array
    {
        $rows = [];
        foreach ($records as $message) {
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
                'display_date'  => $this->dateFormatter->format($message->date),
                'category_name' => $category->name ?? '',
                'category_url'  => '/forum/?id=' . ($category->id ?? ''),
                'section_name'  => $section->name ?? '',
                'section_url'   => '/forum/?type=topics&id=' . ($section->id ?? ''),
            ];
        }

        return $rows;
    }

    /**
     * @param Collection<int, ForumTopic> $records
     * @return array<int, array<string, mixed>>
     */
    private function mapTopics(Collection $records, bool $includeDeleted): array
    {
        $rows = [];
        foreach ($records as $topic) {
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
                'display_date'  => $this->dateFormatter->format($topic->last_post_date),
                'category_name' => $category->name ?? '',
                'category_url'  => '/forum/?id=' . ($category->id ?? ''),
                'section_name'  => $section->name ?? '',
                'section_url'   => '/forum/?type=topics&id=' . ($section->id ?? ''),
            ];
        }

        return $rows;
    }

    /**
     * @param Collection<int, GuestbookEntry> $records
     * @return array<int, array<string, mixed>>
     */
    private function mapComments(Collection $records): array
    {
        $rows = [];
        foreach ($records as $entry) {
            if (! $entry instanceof GuestbookEntry) {
                continue;
            }
            $rows[] = [
                'display_date' => $this->dateFormatter->format((int) $entry->getRawOriginal('time')),
                'text'         => $this->guestbookTextFormatter->formatPost($entry),
            ];
        }

        return $rows;
    }
}
