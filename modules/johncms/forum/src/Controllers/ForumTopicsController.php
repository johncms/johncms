<?php

declare(strict_types=1);

namespace Johncms\Forum\Controllers;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Johncms\Forum\Ban\ForumBans;
use Johncms\Forum\ForumCounters;
use Johncms\Forum\ForumPermissions;
use Johncms\Forum\ForumUtils;
use Johncms\Forum\Messages\ForumMessagesService;
use Johncms\Forum\Models\ForumMessage;
use Johncms\Forum\Models\ForumSection;
use Johncms\Forum\Models\ForumTopic;
use Johncms\Forum\Models\ForumVote;
use Johncms\Forum\Resources\MessageResource;
use Johncms\Forum\Topics\ForumTopicService;
use Johncms\Http\Request;
use Johncms\System\Legacy\Bbcode;
use Johncms\System\Legacy\Tools;
use Johncms\Users\User;
use Johncms\Utility\Numbers;
use Johncms\Validator\Validator;

class ForumTopicsController extends BaseForumController
{
    /**
     * Show topic
     */
    public function showTopic(
        int $id,
        ForumMessagesService $forumMessagesService,
        ForumTopicService $forumTopicService,
        ForumUtils $forumUtils,
        ?User $user,
        ForumCounters $forumCounters
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

        // Build breadcrumbs
        $forumUtils->buildBreadcrumbs($currentTopic->section_id, $currentTopic->name);
        $forumUtils->setMetaForTopic($currentTopic);

        // Increasing the number of views
        $forumTopicService->markAsViewed($currentTopic);

        $access = 0;
        if ($user) {
            // Mark the topic as read
            $forumTopicService->markAsRead($id, $user->id);

            // TODO: Change it
            $online = [
                'users'  => $forumCounters->onlineUsers(),
                'guests' => $forumCounters->onlineGuests(),
            ];

            $currentSection = $currentTopic->section;
            $access = $currentSection->access;
        }

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

        $topicMessages = $forumMessagesService->getTopicMessages($id);
        $messages = MessageResource::createFromCollection($topicMessages);

        return $this->render->render(
            'forum::topic',
            [
                'first_post'       => $first_message,
                'topic'            => $currentTopic,
                'topic_vote'       => $topic_vote ?? null,
                'curators_array'   => ! empty($currentTopic->curators) ? $currentTopic->curators : [],
                'view_count'       => $currentTopic->view_count,
                'pagination'       => $topicMessages->render(),
                'start'            => $start,
                'id'               => $id,
                'token'            => $token ?? null,
                'bbcode'           => di(Bbcode::class)->buttons('new_message', 'msg'),
                'settings_forum'   => $set_forum,
                'write_access'     => $write_access,
                'messages'         => $messages->getItems() ?? [],
                'online'           => $online ?? [],
                'total'            => $topicMessages->total(),
                'files_count'      => $forumSettings['file_counters'] ? Numbers::formatNumber($currentTopic->files_count) : 0,
                'unread_count'     => Numbers::formatNumber($forumCounters->unreadMessages()),
                'filter_by_author' => $filter ?? 0,
                'poll_data'        => $poll_data,
                'permissions'      => [
                    'canManagePosts' => $user?->hasPermission(ForumPermissions::MANAGE_POSTS),
                    'canManageTopic' => $user?->hasPermission(ForumPermissions::MANAGE_TOPICS),
                ],
            ]
        );
    }

    /**
     * Topic creation page
     */
    public function newTopic(int $sectionId, User $user, Request $request, Tools $tools, ForumUtils $forumUtils)
    {
        $set_forum = [
            'farea'    => 0,
            'upfp'     => 0,
            'preview'  => 1,
            'postclip' => 1,
            'postcut'  => 2,
        ];

        $current_section = ForumSection::query()->where('section_type', 1)->where('id', $sectionId)->firstOrFail();

        // Check access
        if (
            ! $user
            || $user->hasBan([ForumBans::CREATE_TOPICS, ForumBans::READ_ONLY])
            || (! $user->hasAnyRole() && config('johncms.mod_forum') === 3)
        ) {
            http_response_code(403);
            return $this->render->render(
                'system::pages/result',
                [
                    'type'          => 'alert-danger',
                    'message'       => __('Access forbidden'),
                    'back_url'      => $current_section->url,
                    'back_url_name' => __('Go to Section'),
                ]
            );
        }

        $data = [
            'name'       => $request->getPost('th', '', FILTER_SANITIZE_SPECIAL_CHARS, ['flag' => FILTER_FLAG_ENCODE_HIGH]),
            'message'    => ForumUtils::topicLink($request->getPost('msg', '')),
            'csrf_token' => $request->getPost('csrf_token', ''),
            'add_files'  => (int) $request->getPost('addfiles', 0),
        ];

        if ($user->hasAnyRole()) {
            $data['meta_keywords'] = $request->getPost('meta_keywords', null, FILTER_SANITIZE_STRING);
            $data['meta_description'] = $request->getPost('meta_description', null, FILTER_SANITIZE_STRING);
        }

        $errors = [];
        if ($request->getPost('submit')) {
            $rules = [
                'name'       => [
                    'NotEmpty',
                    'StringLength'   => ['min' => 3, 'max' => 200],
                    'ModelNotExists' => [
                        'model'   => ForumTopic::class,
                        'field'   => 'name',
                        'exclude' => static function ($query) use ($id) {
                            $query->where('section_id', $id);
                        },
                    ],
                ],
                'message'    => [
                    'NotEmpty',
                    'StringLength'   => ['min' => 4],
                    'ModelNotExists' => [
                        'model'   => ForumMessage::class,
                        'field'   => 'text',
                        'exclude' => static function ($query) use ($user) {
                            $query->where('user_id', $user->id);
                        },
                    ],
                ],
                'csrf_token' => ['Csrf'],
            ];

            $validator = new Validator($data, $rules);

            if ($validator->isValid()) {
                $topic = (new ForumTopic())->create(
                    [
                        'section_id'       => $current_section->id,
                        'created_at'       => Carbon::now(),
                        'user_id'          => $user->id,
                        'user_name'        => $user->name,
                        'name'             => $data['name'],
                        'meta_keywords'    => $data['meta_keywords'] ?? null,
                        'meta_description' => $data['meta_description'] ?? null,
                        'last_post_date'   => time(),
                        'post_count'       => 0,
                        'curators'         => $current_section->access === 1 ? [$user->id => $user->name] : [],
                    ]
                );

                /** @var \Johncms\Http\IpLogger $env */
                $env = di(\Johncms\Http\IpLogger::class);

                $message = (new ForumMessage())->create(
                    [
                        'topic_id'     => $topic->id,
                        'date'         => time(),
                        'user_id'      => $user->id,
                        'user_name'    => $user->name,
                        'ip'           => $env->getIp(false),
                        'ip_via_proxy' => $env->getIpViaProxy(false),
                        'user_agent'   => $env->getUserAgent(),
                        'text'         => $data['message'],
                    ]
                );

                // Пересчитаем топик
                $tools->recountForumTopic($topic->id);

                // Записываем счетчик постов юзера
                $user->update(
                    [
                        'postforum' => ($user->postforum + 1),
                        'lastpost'  => time(),
                    ]
                );

                (new ForumUnread())->create(
                    [
                        'topic_id' => $topic->id,
                        'user_id'  => $user->id,
                        'time'     => time(),
                    ]
                );

                if ($data['add_files'] === 1) {
                    header("Location: ?id=" . $message->id . "&act=addfile");
                } else {
                    header("Location: " . htmlspecialchars_decode($topic->url));
                }
                exit;
            }

            $errors = $validator->getErrors();
        }

        $msg_pre = $tools->checkout($data['message'], 1, 1);
        $msg_pre = $tools->smilies($msg_pre, $user->rights ? 1 : 0);
        $msg_pre = preg_replace('#\[c\](.*?)\[/c\]#si', '<div class="quote">\1</div>', $msg_pre);

        $forumUtils->buildBreadcrumbs($current_section->parent, $current_section->name, $current_section->url);
        $this->navChain->add(__('New Topic'));
        $this->metaTagManager->setAll(__('New Topic'));

        return $this->render->render(
            'forum::new_topic',
            [
                'settings_forum'    => $set_forum,
                'id'                => $sectionId,
                'th'                => $data['name'],
                'add_files'         => ($data['add_files'] === 1),
                'msg'               => $tools->checkout($data['message'], 0, 0),
                'bbcode'            => di(\Johncms\System\Legacy\Bbcode::class)->buttons('new_topic', 'msg'),
                'back_url'          => $current_section->url,
                'show_post_preview' => ! empty($data['name']) && ! empty($data['message']) && ! $request->getPost('submit', null),
                'preview_message'   => $msg_pre,
                'errors'            => $errors,
                'data'              => $data,
            ]
        );
    }
}
