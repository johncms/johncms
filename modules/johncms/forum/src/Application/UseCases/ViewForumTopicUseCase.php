<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\CurrentUser;
use Johncms\Modules\Forum\Application\Access\ForumAccess;
use Johncms\Modules\Forum\Application\Services\ForumPermissions;
use Johncms\Modules\Forum\Application\Services\ForumFormTokens;
use Johncms\Modules\Forum\Application\Uploads\ForumPostFiles;
use Johncms\Modules\Forum\Application\Services\ForumPostRows;
use Johncms\Modules\Forum\Application\Services\ForumUserSettings;
use Carbon\Carbon;
use Johncms\Modules\Forum\Application\DTO\ForumTopicPageResultDTO;
use Johncms\Modules\Forum\Application\Exceptions\ForumNotFoundException;
use Johncms\Modules\Forum\Application\Services\ForumSectionPathService;
use Johncms\Modules\Forum\Application\Services\ForumTopicPathService;
use Johncms\Modules\Forum\Domain\Models\ForumMessage;
use Johncms\Modules\Forum\Domain\Models\ForumTopic;
use Johncms\Modules\Forum\Domain\Models\ForumVote;
use Johncms\Modules\Forum\Domain\Repository\ForumMessageRepositoryInterface;
use Johncms\Modules\Forum\Domain\Repository\ForumTopicRepositoryInterface;
use Johncms\Modules\Forum\Domain\Repository\ForumUnreadRepositoryInterface;
use Johncms\Modules\Forum\Domain\Repository\ForumVoteRepositoryInterface;
use Johncms\Modules\Forum\Domain\Repository\ForumWhoRepositoryInterface;
use Johncms\Notifications\Notification;
use Johncms\Utils\ShortNumberFormatter;

final readonly class ViewForumTopicUseCase
{
    public function __construct(
        private ForumTopicRepositoryInterface $topicRepository,
        private ForumMessageRepositoryInterface $messageRepository,
        private ForumUnreadRepositoryInterface $unreadRepository,
        private ForumVoteRepositoryInterface $voteRepository,
        private ForumWhoRepositoryInterface $whoRepository,
        private ForumSectionPathService $sectionPathService,
        private ForumTopicPathService $topicPathService,
        private CurrentUser $currentUser,
        private ForumFormTokens $formTokens,
        private ForumPostFiles $postFiles,
        private AccessCheckerInterface $accessChecker,
        private ForumAccess $forumAccess,
        private ForumUserSettings $userSettings,
        private ForumPostRows $postRows,
    ) {
    }

    /**
     * @param int[] $filterByUsers
     * @throws ForumNotFoundException
     */
    public function execute(
        string $path,
        int $page,
        bool $showVoteResult,
        bool $incrementViewCount,
        bool $filterEnabled,
        int $filterTopicId,
        array $filterByUsers,
    ): ForumTopicPageResultDTO {
        $parsedPath = $this->topicPathService->parseTopicPath('/forum/' . ltrim($path, '/'));
        if ($parsedPath === null) {
            throw new ForumNotFoundException('Topic path is invalid.');
        }

        $forumSettings = config('forum')['settings'];
        $topic = $this->topicRepository->findByIdWithSection(
            $parsedPath['topicId'],
            (bool) $forumSettings['file_counters']
        );

        if ($topic === null || $topic->section === null) {
            throw new ForumNotFoundException('Topic not found.');
        }

        if ($this->sectionPathService->getSectionPath($topic->section) !== $parsedPath['sectionPath']) {
            throw new ForumNotFoundException('Topic path does not match section path.');
        }

        if ($this->topicPathService->getTopicSlug($topic) !== $parsedPath['topicSlug']) {
            throw new ForumNotFoundException('Topic slug does not match canonical slug.');
        }

        if ($incrementViewCount) {
            $this->topicRepository->incrementViewCount($topic->id);
            $topic->view_count = (int) $topic->view_count + 1;
        }

        if ($this->currentUser->isValid()) {
            $this->unreadRepository->markTopicAsRead($topic->id, $this->currentUser->id(), time());
        }

        $online = [];
        $access = 0;
        if ($this->currentUser->isValid()) {
            $online = [
                'online_u' => $this->whoRepository->countTopicUsers($topic->id),
                'online_g' => $this->whoRepository->countTopicGuests($topic->id),
            ];
            $access = (int) ($topic->section->access ?? 0);
        }

        $isFilterEnabled = $filterEnabled && $filterTopicId === $topic->id;
        $perPage = (int) $this->currentUser->user()->config->kmess;
        $start = ($page - 1) * $perPage;

        $newestFirst = $this->userSettings->newestFirst();
        $filterUserIds = $isFilterEnabled ? $filterByUsers : [];
        $total = $this->messageRepository->countAllByTopicId($topic->id, $filterUserIds);
        $messagesCollection = $this->messageRepository->getByTopicIdWithUsersAndFiles(
            topicId: $topic->id,
            upfp: $newestFirst,
            limit: $perPage,
            offset: $start,
            filterUserIds: $filterUserIds,
        );

        // A curator moderates the topic they were appointed to: the permission is the same one
        // a moderator holds, and the topic is the subject that narrows it down.
        $canModerateTopic = $this->accessChecker->allows(ForumPermissions::TOPIC_MODERATE, $topic);

        $banned = $this->forumAccess->isBanned();
        $editableIds = [];
        $i = 1;
        foreach ($messagesCollection as $message) {
            if (! $banned && $this->mayEdit($message, $i, $canModerateTopic, $newestFirst, $access, $start, $total)) {
                $editableIds[$message->id] = true;
            }
            ++$i;
        }

        $canReply = $this->currentUser->isValid()
            && ! $banned
            && (! $topic->closed || $canModerateTopic);
        $messages = $this->postRows->present(
            messages: $messagesCollection,
            editableIds: $editableIds,
            canReply: $canReply,
            page: $page,
        );

        if ($this->currentUser->isValid()) {
            $postIds = $messagesCollection->pluck('id')->all();
            if ($postIds !== []) {
                Notification::query()
                    ->where('module', 'forum')
                    ->where('event_type', 'new_message')
                    ->whereNull('read_at')
                    ->whereIn('entity_id', $postIds)
                    ->update(['read_at' => Carbon::now()]);
            }
        }

        $topicVote = null;
        $pollData = [];
        if ($topic->has_poll) {
            $topicVote = $this->voteRepository->findPollByTopicWithAnswers($topic->id);
            if ($topicVote instanceof ForumVote) {
                $pollData = $this->buildPollData($topic, $topicVote, $showVoteResult);
            }
        }

        $writeAccess = false;
        if (
            ! $banned
            && (
                ($this->currentUser->isValid() && ! $topic->closed && $access !== 4 && $this->accessChecker->allows(ForumPermissions::POST))
                || $this->accessChecker->allows(ForumPermissions::TOPIC_MODERATE)
            )
        ) {
            $writeAccess = true;
        }

        $token = $writeAccess ? $this->formTokens->issue() : null;

        $curators = [];
        foreach ((array) $topic->curators as $userId => $userName) {
            $curators[] = ['id' => $userId, 'name' => $userName];
        }

        $canonical = config('johncms.homeurl') . $this->topicPathService->getTopicUrl($topic, $page > 1 ? $page : null);

        return new ForumTopicPageResultDTO(
            viewData: [
                'topic'            => $topic,
                'topic_vote'       => $topicVote,
                'curators'         => $curators,
                'view_count'       => $topic->view_count,
                'page'             => $page,
                'id'               => $topic->id,
                'token'            => $token,
                'file_limits'      => $writeAccess ? $this->postFiles->limits() : null,
                'newest_first'     => $newestFirst,
                'write_access'     => $writeAccess,
                'messages'         => $messages,
                'online'           => $online,
                'total'            => $total,
                'files_count'      => $forumSettings['file_counters']
                    ? ShortNumberFormatter::format((int) ($topic->files_count ?? 0))
                    : 0,
                'filter_by_author' => $isFilterEnabled,
                'poll_data'        => $pollData,
            ],
            canonical: $canonical,
            title: (string) $topic->name,
        );
    }

    /**
     * A moderator changes any post; the author changes their newest post for five minutes, and the
     * first post of the topic at any time in a section that lets them.
     *
     * @param int $position The place of the post on the page, from one.
     */
    private function mayEdit(ForumMessage $message, int $position, bool $canModerate, bool $newestFirst, int $access, int $start, int $total): bool
    {
        if ($canModerate) {
            return true;
        }

        if ($message->user_id !== $this->currentUser->id()) {
            return false;
        }

        $isNewest = $newestFirst ? $start === 0 && $position === 1 : $start + $position === $total;
        $isFirst = $newestFirst ? $start + $position === $total : $start === 0 && $position === 1;

        if ($isFirst && $access === 2) {
            return true;
        }

        return $isNewest && $message->date > time() - 300;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPollData(
        ForumTopic $topic,
        ForumVote $topicVote,
        bool $showVoteResult,
    ): array {
        $results = [];
        $top = 0;
        foreach ($topicVote->answers as $answer) {
            $top = max($top, (int) $answer->count);
        }

        foreach ($topicVote->answers as $answer) {
            $count = (int) $answer->count;
            $results[] = [
                'id'           => (int) $answer->id,
                'name'         => (string) $answer->name,
                'count'        => $count,
                'vote_percent' => $topicVote->count ? (int) round(100 / $topicVote->count * $count) : 0,
                // The answers that lead, ties included; none before the first vote.
                'leader'       => $top > 0 && $count === $top,
            ];
        }

        // The column of the vote of the visitor is selected for a member only: a guest has none.
        $canVote = ! $topic->closed
            && $this->currentUser->isValid()
            && ! $this->forumAccess->isBanned()
            && (int) $topicVote->getAttribute('vote_user') !== 1;

        return [
            'show_form' => $canVote && ! $showVoteResult,
            'can_vote'  => $canVote,
            'results'   => $results,
        ];
    }
}
