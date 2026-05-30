<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\UseCases;

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
use Johncms\System\Legacy\Tools;
use Johncms\Users\User;

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
        private Tools $tools,
        private User $currentUser,
    ) {
    }

    /**
     * @param int[] $filterByUsers
     * @throws ForumNotFoundException
     */
    public function execute(
        string $path,
        int $page,
        bool $showClip,
        bool $showVoteResult,
        bool $incrementViewCount,
        array $setForum,
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
            $this->unreadRepository->markTopicAsRead($topic->id, $this->currentUser->id, time());
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
        $perPage = (int) $this->currentUser->config->kmess;
        $start = ($page - 1) * $perPage;

        $messagesPaginator = $this->messageRepository->paginateByTopicIdWithUsersAndFiles(
            topicId: $topic->id,
            upfp: ! empty($setForum['upfp']),
            perPage: $perPage,
            filterUserIds: $isFilterEnabled ? $filterByUsers : [],
        );
        $total = $messagesPaginator->total();

        $curator = $this->currentUser->rights < 6
            && $this->currentUser->rights !== 3
            && array_key_exists($this->currentUser->id, (array) $topic->curators)
            && $this->currentUser->isValid();

        $firstMessage = null;
        if (
            $showClip
            || (
                (int) $setForum['postclip'] === 2
                && (
                    (! empty($setForum['upfp']) && $start < (int) ceil($total - $perPage))
                    || (empty($setForum['upfp']) && $start > 0)
                )
            )
        ) {
            $firstMessage = $this->messageRepository->findFirstByTopicIdWithUsers($topic->id);
        }

        $i = 1;
        $canReplyInClosedTopic = $this->currentUser->rights === 3 || $this->currentUser->rights >= 6;
        $messages = $messagesPaginator->getCollection()->map(
            function (ForumMessage $message) use ($curator, $setForum, $access, &$i, $start, $total, $topic, $canReplyInClosedTopic, $page): ForumMessage {
                if (
                    (
                        (($this->currentUser->rights === 3 || $this->currentUser->rights >= 6 || $curator)
                            && $this->currentUser->rights >= $message->rights
                        )
                        || ($i === 1 && $access === 2 && $message->user_id === $this->currentUser->id)
                        || ($message->user_id === $this->currentUser->id
                            && empty($setForum['upfp'])
                            && ($start + $i) === $total
                            && $message->date > time() - 300
                        )
                        || ($message->user_id === $this->currentUser->id
                            && ! empty($setForum['upfp'])
                            && $start === 0
                            && $i === 1
                            && $message->date > time() - 300
                        )
                    )
                ) {
                    $message->can_edit = true;
                }

                if (
                    $this->currentUser->id !== $message->user_id
                    && $this->currentUser->isValid()
                    && (! $topic->closed || $canReplyInClosedTopic)
                ) {
                    $message->reply_url = '/forum/reply-message/' . $message->id . '/' . ($page > 1 ? '?page=' . $page : '');
                    $message->quote_url = '/forum/reply-message/' . $message->id . '/' . ($page > 1 ? '?page=' . $page . '&amp;quote=1' : '?quote=1');
                }

                ++$i;

                return $message;
            }
        );

        if ($this->currentUser->isValid()) {
            $postIds = $messages->pluck('id')->all();
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
                $pollData = $this->buildPollData($topic, $topicVote, $showClip, $showVoteResult);
            }
        }

        $writeAccess = false;
        if (
            ($this->currentUser->isValid() && ! $topic->closed && config('johncms.mod_forum') !== 3 && $access !== 4)
            || $this->currentUser->rights >= 7
        ) {
            $writeAccess = true;
        }

        $token = null;
        if ($writeAccess && ! empty($setForum['farea'])) {
            $token = random_int(1000, 100000);
            $_SESSION['token'] = $token;
        }

        $curatorsArray = [];
        if (! empty($topic->curators)) {
            foreach ($topic->curators as $key => $value) {
                $curatorsArray[] = '<a href="/profile/' . $key . '">' . $value . '</a>';
            }
        }

        $canonical = config('johncms.homeurl') . $this->topicPathService->getTopicUrl($topic, $page > 1 ? $page : null);

        return new ForumTopicPageResultDTO(
            viewData: [
                'first_post'       => $firstMessage,
                'topic'            => $topic,
                'topic_vote'       => $topicVote,
                'curators_array'   => $curatorsArray,
                'view_count'       => $topic->view_count,
                'pagination'       => $messagesPaginator->render(),
                'page'             => $page,
                'id'               => $topic->id,
                'token'            => $token,
                'settings_forum'   => $setForum,
                'write_access'     => $writeAccess,
                'messages'         => $messages,
                'online'           => $online,
                'total'            => $total,
                'files_count'      => $forumSettings['file_counters']
                    ? $this->tools->formatNumber((int) ($topic->files_count ?? 0))
                    : 0,
                'filter_by_author' => $isFilterEnabled,
                'poll_data'        => $pollData,
            ],
            canonical: $canonical,
            title: (string) $topic->name,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPollData(
        ForumTopic $topic,
        ForumVote $topicVote,
        bool $showClip,
        bool $showVoteResult,
    ): array {
        $results = [];
        $colorClasses = config('forum')['answer_colors'];

        foreach ($topicVote->answers as $answer) {
            $vote = $answer->toArray();
            $countVote = $topicVote->count ? round(100 / $topicVote->count * (int) $vote['count']) : 0;
            $color = null;
            if ($countVote > 0 && $countVote <= 25) {
                $color = $colorClasses['0_25'];
            } elseif ($countVote > 25 && $countVote <= 50) {
                $color = $colorClasses['25_50'];
            } elseif ($countVote > 50 && $countVote <= 75) {
                $color = $colorClasses['50_75'];
            } elseif ($countVote > 75 && $countVote <= 100) {
                $color = $colorClasses['75_100'];
            }

            $vote['color_class'] = $color;
            $vote['vote_percent'] = $countVote;
            $results[] = $vote;
        }

        return [
            'show_form' => (
                ! $topic->closed
                && ! $showVoteResult
                && $this->currentUser->isValid()
                && (int) ($topicVote->vote_user ?? 0) !== 1
            ),
            'results'   => $results,
            'clip'      => $showClip ? '&amp;clip' : '',
        ];
    }
}
