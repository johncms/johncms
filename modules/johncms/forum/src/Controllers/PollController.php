<?php

declare(strict_types=1);

namespace Johncms\Forum\Controllers;

use Johncms\Exceptions\PageNotFoundException;
use Johncms\Forum\Models\ForumTopic;
use Johncms\Forum\Models\ForumVote;
use Johncms\Forum\Models\ForumVoteUser;
use Johncms\Forum\Resources\VoteUserResource;
use Johncms\Forum\Services\ForumTopicService;
use Johncms\Http\Request;
use Johncms\Http\Response\RedirectResponse;
use Johncms\Users\User;

class PollController extends BaseForumController
{
    public function add(int $topicId, Request $request, ForumTopicService $topicService): string
    {
        $topic = ForumTopic::query()->findOrFail($topicId);
        $this->metaTagManager->setAll(__('Add Poll'));

        if ($request->isPost() && $request->getPost('submit')) {
            $vote_name = mb_substr(trim($_POST['name_vote']), 0, 50);

            if (! empty($vote_name) && ! empty($_POST[0]) && ! empty($_POST[1]) && ! empty($_POST['count_vote'])) {
                ForumVote::query()->create(
                    [
                        'name'  => $vote_name,
                        'time'  => time(),
                        'type'  => 1,
                        'topic' => $topicId,
                    ]
                );

                $topicService->update($topic, ['has_poll' => true]);
                $vote_count = (int) $_POST['count_vote'];

                if ($vote_count > 20) {
                    $vote_count = 20;
                } else {
                    if ($vote_count < 2) {
                        $vote_count = 2;
                    }
                }

                for ($vote = 0; $vote < $vote_count; $vote++) {
                    $text = mb_substr(trim($_POST[$vote]), 0, 30);
                    if (empty($text)) {
                        continue;
                    }
                    ForumVote::query()->create(
                        [
                            'name'  => $text,
                            'type'  => 2,
                            'topic' => $topicId,
                        ]
                    );
                }
                return $this->render->render(
                    'system::pages/result',
                    [
                        'title'         => __('Add Poll'),
                        'page_title'    => __('Add Poll'),
                        'type'          => 'alert-success',
                        'message'       => __('Poll added'),
                        'back_url'      => $topic->url,
                        'back_url_name' => __('Continue'),
                    ]
                );
            } else {
                return $this->render->render(
                    'system::pages/result',
                    [
                        'title'         => __('Add Poll'),
                        'page_title'    => __('Add Poll'),
                        'type'          => 'alert-danger',
                        'message'       => __('The required fields are not filled'),
                        'back_url'      => route('forum.addPoll', ['topicId' => $topicId]),
                        'back_url_name' => __('Repeat'),
                    ]
                );
            }
        }

        $count_vote = $request->getPost('count_vote', 0, FILTER_VALIDATE_INT);

        if (isset($_POST['plus'])) {
            ++$count_vote;
        } elseif (isset($_POST['minus'])) {
            --$count_vote;
        }

        if (empty($_POST['count_vote']) || $_POST['count_vote'] < 2) {
            $count_vote = 2;
        } elseif ($_POST['count_vote'] > 20) {
            $count_vote = 20;
        }

        $votes = [];
        for ($vote = 0; $vote < $count_vote; $vote++) {
            $votes[] = [
                'input_name'  => $vote,
                'input_label' => __('Answer') . ' ' . ($vote + 1),
                'input_value' => htmlentities($_POST[$vote] ?? '', ENT_QUOTES, 'UTF-8'),
            ];
        }

        return $this->render->render(
            'johncms/forum::add_poll',
            [
                'id'         => $topicId,
                'actionUrl'  => route('forum.addPoll', ['topicId' => $topicId]),
                'back_url'   => $topic->url,
                'count_vote' => $count_vote,
                'poll_name'  => htmlentities($_POST['name_vote'] ?? '', ENT_QUOTES, 'UTF-8'),
                'votes'      => $votes,
            ]
        );
    }

    public function edit(int $topicId, Request $request): RedirectResponse | string
    {
        $topic = ForumTopic::query()->findOrFail($topicId);
        $poll = ForumVote::query()->where('type', 1)->where('topic', $topicId)->firstOrFail();
        $pollAnswers = ForumVote::query()->where('type', 2)->where('topic', $topicId)->get();
        $pollAnswersCount = $pollAnswers->count();

        if (isset($_GET['delvote']) && ! empty($_GET['vote'])) {
            $vote = $request->getQuery('vote', 0, FILTER_VALIDATE_INT);
            $deleteVote = ForumVote::query()->where('type', 2)->where('topic', $topicId)->where('id', $vote)->first();

            if ($pollAnswersCount <= 2) {
                return new RedirectResponse(route('forum.editPoll', ['topicId' => $topicId]));
            }

            if ($deleteVote) {
                if (isset($_GET['yes'])) {
                    ForumVote::query()->where('id', $vote)->delete();
                    $countus = ForumVoteUser::query()->where('vote', $vote)->where('topic', $topicId)->count();
                    $topic_vote = ForumVote::query()->where('type', 1)->where('topic', $topicId)->first();
                    $totalCount = $topic_vote->count - $countus;
                    $topic_vote->update(['count' => $totalCount]);
                    ForumVoteUser::query()->where('vote', $vote)->delete();
                    return new RedirectResponse(route('forum.editPoll', ['topicId' => $topicId]));
                } else {
                    return $this->render->render(
                        'johncms/forum::delete_answer',
                        [
                            'title'      => __('Delete Answer'),
                            'page_title' => __('Delete Answer'),
                            'id'         => $topicId,
                            'delete_url' => $request->getQueryString([], ['vote' => $vote, 'delvote' => 1, 'yes' => 1]),
                            'back_url'   => route('forum.editPoll', ['topicId' => $topicId]),
                        ]
                    );
                }
            } else {
                return new RedirectResponse(route('forum.editPoll', ['topicId' => $topicId]));
            }
        }

        if (isset($_POST['submit'])) {
            $vote_name = mb_substr(trim($_POST['name_vote']), 0, 250);
            if (! empty($vote_name)) {
                $poll->update(['name' => $vote_name]);
            }

            foreach ($pollAnswers as $pollAnswer) {
                if (! empty($_POST[$pollAnswer->id . 'vote'])) {
                    $text = mb_substr(trim($_POST[$pollAnswer->id . 'vote']), 0, 250);
                    $pollAnswer->update(['name' => $text]);
                }
            }

            for ($vote = $pollAnswersCount; $vote < 20; $vote++) {
                if (! empty($_POST[$vote])) {
                    $text = mb_substr(trim($_POST[$vote]), 0, 250);
                    ForumVote::query()->create(['name' => $text, 'type' => 2, 'topic' => $topicId]);
                }
            }
            return $this->render->render(
                'system::pages/result',
                [
                    'title'         => __('Edit Poll'),
                    'page_title'    => __('Edit Poll'),
                    'type'          => 'alert-success',
                    'message'       => __('Poll changed'),
                    'back_url'      => $topic->last_page_url,
                    'back_url_name' => __('Continue'),
                ]
            );
        }

        $answers = [];
        $i = 0;
        foreach ($pollAnswers as $pollAnswer) {
            $answers[] = [
                'input_name'  => $pollAnswer->id . 'vote',
                'input_label' => __('Answer') . ' ' . ($i + 1),
                'input_value' => $pollAnswer->name,
                'delete_url'  => $pollAnswersCount > 2 ? $request->getQueryString([], ['vote' => $pollAnswer->id, 'delvote' => 1]) : '',
            ];
            ++$i;
        }

        $count_vote = isset($_POST['count_vote']) ? (int) $_POST['count_vote'] : $pollAnswersCount;
        if ($pollAnswersCount < 20) {
            if (isset($_POST['plus'])) {
                ++$count_vote;
            } elseif (isset($_POST['minus'])) {
                --$count_vote;
            }

            if (empty($count_vote)) {
                $count_vote = $pollAnswersCount;
            } elseif ($count_vote > 20) {
                $count_vote = 20;
            }

            for ($vote = $i; $vote < $count_vote; $vote++) {
                $answers[] = [
                    'input_name'  => $vote,
                    'input_label' => __('Answer') . ' ' . ($vote + 1),
                    'input_value' => htmlentities($_POST[$vote] ?? '', ENT_QUOTES, 'UTF-8'),
                ];
            }
        }

        return $this->render->render(
            'johncms/forum::edit_poll',
            [
                'title'      => __('Edit Poll'),
                'page_title' => __('Edit Poll'),
                'id'         => $topicId,
                'back_url'   => $topic->last_page_url,
                'saved_vote' => $pollAnswersCount,
                'count_vote' => $count_vote,
                'poll_name'  => $poll->name,
                'votes'      => $answers,
                'actionUrl'  => route('forum.editPoll', ['topicId' => $topicId]),
            ]
        );
    }

    public function vote(int $topicId, User $user, Request $request): string
    {
        $topic = ForumTopic::query()->withoutDeletedForUsers()->findOrFail($topicId);
        $voteId = $request->getPost('vote', 0, FILTER_VALIDATE_INT);

        // Check if exists the given option
        $vote = ForumVote::query()->where('type', 2)->where('id', $voteId)->where('topic', $topicId)->firstOrFail();

        $voteUser = ForumVoteUser::query()->where('user', $user->id)->where('topic', $topicId)->count();
        if ($voteUser > 0) {
            throw new PageNotFoundException();
        }

        ForumVoteUser::query()->create(['topic' => $topicId, 'user' => $user->id, 'vote' => $voteId]);
        $vote->increment('count');

        $baseVote = ForumVote::query()->where('type', 1)->where('topic', $topicId)->first();
        $baseVote->increment('count');

        return $this->render->render(
            'system::pages/result',
            [
                'title'         => __('Forum'),
                'page_title'    => __('Forum'),
                'type'          => 'alert-success',
                'message'       => __('Vote accepted'),
                'back_url'      => $topic->last_page_url,
                'back_url_name' => __('Back'),
            ]
        );
    }

    public function delete(int $topicId, Request $request): string
    {
        $topic = ForumTopic::query()->findOrFail($topicId);

        if ($request->getPost('confirm', 0) === 'yes') {
            (new ForumVote())->where('topic', $topicId)->delete();
            (new ForumVoteUser())->where('topic', $topicId)->delete();
            (new ForumTopic())->where('id', $topicId)->update(['has_poll' => null]);

            return $this->render->render(
                'system::pages/result',
                [
                    'title'         => __('Delete Poll'),
                    'type'          => 'alert-success',
                    'message'       => __('Poll deleted'),
                    'back_url'      => $topic->last_page_url,
                    'back_url_name' => __('Back'),
                ]
            );
        }

        return $this->render->render(
            'johncms/forum::delete_poll',
            [
                'actionUrl' => route('forum.deletePoll', ['topicId' => $topicId]),
                'id'        => $topicId,
                'back_url'  => $topic->last_page_url,
            ]
        );
    }

    public function users(int $topicId): string
    {
        $topic = ForumTopic::query()->findOrFail($topicId);
        $poll = ForumVote::query()->where('type', 1)->where('topic', $topicId)->firstOrFail();
        $users = ForumVoteUser::query()->where('topic', $topicId)->with('userData', 'userData.activity')->paginate();
        $userResource = VoteUserResource::createFromCollection($users);

        $this->metaTagManager->setAll(__('Who voted in the poll'));

        return $this->render->render(
            'johncms/forum::voted_users',
            [
                'empty_message' => __('No one has voted in this poll yet'),
                'poll_name'     => $poll->name,
                'items'         => $userResource->getItems(),
                'pagination'    => $users->render(),
                'total'         => $users->total(),
                'id'            => $topicId,
                'backUrl'       => $topic->last_page_url,
            ]
        );
    }
}
