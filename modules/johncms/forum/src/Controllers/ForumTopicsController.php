<?php

declare(strict_types=1);

namespace Johncms\Forum\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Johncms\Forum\ForumCounters;
use Johncms\Forum\ForumUtils;
use Johncms\Forum\Messages\ForumMessagesService;
use Johncms\Forum\Models\ForumMessage;
use Johncms\Forum\Models\ForumTopic;
use Johncms\Forum\Models\ForumUnread;
use Johncms\Forum\Models\ForumVote;
use Johncms\Forum\Resources\MessageResource;
use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\Users\User;
use Johncms\Utility\Numbers;

class ForumTopicsController extends BaseForumController
{
    /**
     * Show topic
     */
    public function showTopic(
        int $id,
        ForumMessagesService $forumMessagesService,
        ForumUtils $forumUtils,
        Request $request,
        ?User $user,
        ForumCounters $forumCounters,
        Session $session
    ): string {
        $forumSettings = di('config')['forum']['settings'];

        $set_forum = [
            'farea'    => 0,
            'upfp'     => 0,
            'preview'  => 1,
            'postclip' => 1,
            'postcut'  => 2,
        ];

        // Getting data for the current topic
        try {
            $currentTopic = ForumTopic::query()
                ->when($forumSettings['file_counters'], function (Builder $builder) {
                    return $builder->withCount('files');
                })
                ->findOrFail($id);
        } catch (ModelNotFoundException) {
            ForumUtils::notFound();
        }

        $this->metaTagManager->setTitle($currentTopic->name);
        $this->metaTagManager->setPageTitle($currentTopic->name);

        // Build breadcrumbs
        $forumUtils->buildBreadcrumbs($currentTopic->section_id, $currentTopic->name);

        $access = 0;
        if ($user) {
            // Mark the topic as read
            ForumUnread::query()->updateOrInsert(['topic_id' => $id, 'user_id' => $user->id], ['time' => time()]);

            // TODO: Change it
            $online = [
                'users'  => $forumCounters->onlineUsers(),
                'guests' => $forumCounters->onlineGuests(),
            ];

            $currentSection = $currentTopic->section;
            $access = $currentSection->access;
        }

        // Increasing the number of views
        if (empty($session->get('viewed_topics')) || ! in_array($currentTopic->id, $session->get('viewed_topics', []))) {
            $currentTopic->update(['view_count' => $currentTopic->view_count + 1]);
            $_SESSION['viewed_topics'][] = $currentTopic->id;
        }


        // Счетчик постов темы
        // $total = $message->total();

        $poll_data = [];
        if ($currentTopic->has_poll) {
            $clip_forum = isset($_GET['clip']) ? '&amp;clip' : '';
            $topic_vote = (new ForumVote())
                ->voteUser()
                ->where('type', '=', 1)
                ->where('topic', '=', $id)
                ->first();

            $poll_data['show_form'] = (! $currentTopic->closed && ! isset($_GET['vote_result']) && $user->is_valid && $topic_vote->vote_user !== 1);
            $poll_data['results'] = [];

            $color_classes = di('config')['forum']['answer_colors'];
            foreach ($topic_vote->answers as $answer) {
                $vote = $answer->toArray();
                $count_vote = $topic_vote->count ? round(100 / $topic_vote->count * $vote['count']) : 0;
                $color = null;
                if ($count_vote > 0 && $count_vote <= 25) {
                    $color = $color_classes['0_25'];
                } elseif ($count_vote > 25 && $count_vote <= 50) {
                    $color = $color_classes['25_50'];
                } elseif ($count_vote > 50 && $count_vote <= 75) {
                    $color = $color_classes['50_75'];
                } elseif ($count_vote > 75 && $count_vote <= 100) {
                    $color = $color_classes['75_100'];
                }

                $vote['color_class'] = $color;
                $vote['vote_percent'] = $count_vote;
                $poll_data['results'][] = $vote;
            }

            $poll_data['clip'] = $clip_forum;
        }

        // Получаем данные о кураторах темы
        $curator = false;
        if ($user->rights < 6 && $user->rights !== 3 && array_key_exists($user->id, $currentTopic->curators) && $user->is_valid) {
            $curator = true;
        }

        // Fixed first post
        $first_message = null;
        $start = 0;
        if (isset($_GET['clip']) || ($set_forum['postclip'] === 2 && ($set_forum['upfp'] ? $start < (ceil($total - $user->set_user->kmess)) : $start > 0))) {
            $first_message = (new ForumMessage())
                ->users()
                ->where('topic_id', '=', $id)
                ->orderBy('id')
                ->first();
        }

        // Нижнее поле "Написать"
        $write_access = false;
        if (($user && ! $currentTopic->closed && config('johncms.mod_forum') !== 3 && $access !== 4) || ($user->rights >= 7)) {
            $write_access = true;
            if ($set_forum['farea']) {
                $token = mt_rand(1000, 100000);
                $_SESSION['token'] = $token;
            }
        }

        // Список кураторов
        $curators_array = [];
        if (! empty($currentTopic->curators)) {
            foreach ($currentTopic->curators as $key => $value) {
                $curators_array[] = '<a href="/profile/?user=' . $key . '">' . $value . '</a>';
            }
        }

        // Setting the canonical URL
        $page = $request->getQuery('page', 0, FILTER_VALIDATE_INT);
        $canonical = config('johncms.homeurl') . $currentTopic->url;
        if ($page > 1) {
            $canonical .= '&page=' . $page;
        }

        $this->render->addData(
            [
                'canonical'   => $canonical,
                'title'       => htmlspecialchars_decode($currentTopic->name),
                'page_title'  => htmlspecialchars_decode($currentTopic->name),
                'keywords'    => $currentTopic->calculated_meta_keywords,
                'description' => $currentTopic->calculated_meta_description,
            ]
        );

        $topicMessages = $forumMessagesService->getTopicMessages($id);
        $messages = MessageResource::createFromCollection($topicMessages);

        return $this->render->render(
            'forum::topic',
            [
                'first_post'       => $first_message,
                'topic'            => $currentTopic,
                'topic_vote'       => $topic_vote ?? null,
                'curators_array'   => $curators_array,
                'view_count'       => $currentTopic->view_count,
                'pagination'       => $topicMessages->render(),
                'start'            => $start,
                'id'               => $id,
                'token'            => $token ?? null,
                'bbcode'           => di(\Johncms\System\Legacy\Bbcode::class)->buttons('new_message', 'msg'),
                'settings_forum'   => $set_forum,
                'write_access'     => $write_access,
                'messages'         => $messages->getItems() ?? [],
                'online'           => $online ?? [],
                'total'            => $topicMessages->total(),
                'files_count'      => $forumSettings['file_counters'] ? Numbers::formatNumber($currentTopic->files_count) : 0,
                'unread_count'     => Numbers::formatNumber($forumCounters->unreadMessages()),
                'filter_by_author' => $filter ?? 0,
                'poll_data'        => $poll_data,
            ]
        );
    }
}
