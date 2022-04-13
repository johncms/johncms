<?php

declare(strict_types=1);

namespace Johncms\Forum\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Johncms\Forum\ForumCounters;
use Johncms\Forum\ForumPermissions;
use Johncms\Forum\ForumUtils;
use Johncms\Forum\Messages\ForumMessagesService;
use Johncms\Forum\Models\ForumMessage;
use Johncms\Forum\Models\ForumTopic;
use Johncms\Forum\Models\ForumVote;
use Johncms\Forum\Resources\MessageResource;
use Johncms\Forum\Topics\ForumTopicService;
use Johncms\System\Legacy\Tools;
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
                'permissions'      => [
                    'canManagePosts' => $user?->hasPermission(ForumPermissions::MANAGE_POSTS),
                    'canManageTopic' => $user?->hasPermission(ForumPermissions::MANAGE_TOPICS),
                ],
            ]
        );
    }

    public function addMessage(int $topicId, ?User $user, Tools $tools)
    {
        $set_forum = [
            'farea'    => 0,
            'upfp'     => 0,
            'preview'  => 1,
            'postclip' => 1,
            'postcut'  => 2,
        ];

        $currentTopic = ForumTopic::query()->findOrFail($topicId);
        // Добавление простого сообщения
        if (($currentTopic->deleted || $currentTopic->closed) && ! $user?->hasAnyRole()) {
            // Проверка, закрыта ли тема
            return $this->render->render(
                'system::pages/result',
                [
                    'title'         => __('New message'),
                    'type'          => 'alert-danger',
                    'message'       => __('You cannot write in a closed topic'),
                    'back_url'      => '/forum/?type=topic&amp;id=' . $topicId,
                    'back_url_name' => __('Back'),
                ]
            );
        }

        $msg = isset($_POST['msg']) ? trim($_POST['msg']) : '';
        //Обрабатываем ссылки
        $msg = preg_replace_callback(
            '~\\[url=(http://.+?)\\](.+?)\\[/url\\]|(http://(www.)?[0-9a-zA-Z\.-]+\.[0-9a-zA-Z]{2,6}[0-9a-zA-Z/\?\.\~&amp;_=/%-:#]*)~',
            '\Johncms\Forum\ForumUtils::forumLink',
            $msg
        );

        if (
            isset($_POST['submit'])
            && ! empty($_POST['msg'])
            && isset($_POST['token'], $_SESSION['token'])
            && $_POST['token'] == $_SESSION['token']
        ) {
            // Проверяем на минимальную длину
            if (mb_strlen($msg) < 4) {
                echo $view->render(
                    'system::pages/result',
                    [
                        'title'         => __('New message'),
                        'type'          => 'alert-danger',
                        'message'       => __('Text is too short'),
                        'back_url'      => '/forum/?type=topic&amp;id=' . $id,
                        'back_url_name' => __('Back'),
                    ]
                );
                exit;
            }

            // Проверяем, не повторяется ли сообщение?
            $req = $db->query("SELECT * FROM `forum_messages` WHERE `user_id` = '" . $user->id . "' ORDER BY `date` DESC");

            if ($req->rowCount()) {
                $res = $req->fetch();
                if ($msg == $res['text']) {
                    echo $view->render(
                        'system::pages/result',
                        [
                            'title'         => __('New message'),
                            'type'          => 'alert-danger',
                            'message'       => __('Message already exists'),
                            'back_url'      => '/forum/?type=topic&amp;id=' . $id . '&amp;start=' . $start,
                            'back_url_name' => __('Back'),
                        ]
                    );
                    exit;
                }
            }

            // Удаляем фильтр, если он был
            if (isset($_SESSION['fsort_id']) && $_SESSION['fsort_id'] == $id) {
                unset($_SESSION['fsort_id'], $_SESSION['fsort_users']);
            }

            unset($_SESSION['token']);

            // Проверяем, было ли последнее сообщение от того же автора?
            $req = $db->query(
                'SELECT *, CHAR_LENGTH(`text`) AS `strlen` FROM `forum_messages`
            WHERE `topic_id` = ' . $id . ($user->rights >= 7 ? '' : " AND (`deleted` != '1' OR deleted IS NULL)") . '
            ORDER BY `date` DESC LIMIT 1'
            );

            $update = false;
            if ($req->rowCount()) {
                $update = true;

                $check_files = false;
                // Если пост текущего пользователя, то проверяем наличие у него файлов
                if ($res['user_id'] == $user->id) {
                    $check_files = $db->query('SELECT id FROM cms_forum_files WHERE post = ' . $res['id'])->rowCount();
                }

                $res = $req->fetch();
                if (
                    ! isset($_POST['addfiles']) &&
                    $res['date'] + 3600 < strtotime('+ 1 hour') &&
                    $res['strlen'] + strlen($msg) < 65536 &&
                    $res['user_id'] == $user->id &&
                    empty($check_files)
                ) {
                    $newpost = $res['text'];

                    if (strpos($newpost, '[timestamp]') === false) {
                        $newpost = '[timestamp]' . date('d.m.Y H:i', $res['date']) . '[/timestamp]' . PHP_EOL . $newpost;
                    }

                    $newpost .= PHP_EOL . PHP_EOL . '[timestamp]' . date('d.m.Y H:i', time()) . '[/timestamp]' . PHP_EOL . $msg;

                    // Обновляем пост
                    $db->prepare(
                        'UPDATE `forum_messages` SET
                      `text` = ?,
                      `date` = ?
                      WHERE `id` = ' . $res['id']
                    )->execute([$newpost, time()]);
                } else {
                    $update = false;
                    /** @var \Johncms\Http\IpLogger $env */
                    $env = di(\Johncms\Http\IpLogger::class);

                    // Добавляем сообщение в базу
                    $db->prepare(
                        '
                      INSERT INTO `forum_messages` SET
                      `topic_id` = ?,
                      `date` = ?,
                      `user_id` = ?,
                      `user_name` = ?,
                      `ip` = ?,
                      `ip_via_proxy` = ?,
                      `user_agent` = ?,
                      `text` = ?
                    '
                    )->execute(
                        [
                            $id,
                            time(),
                            $user->id,
                            $user->name,
                            $env->getIp(),
                            $env->getIpViaProxy(),
                            $env->getUserAgent(),
                            $msg,
                        ]
                    );

                    $fadd = $db->lastInsertId();
                }
            }

            $cnt_messages = $db->query("SELECT COUNT(*) FROM `forum_messages` WHERE `topic_id` = '${id}' AND (`deleted` != '1' OR `deleted` IS NULL)")->fetchColumn();
            $cnt_all_messages = $db->query("SELECT COUNT(*) FROM `forum_messages` WHERE `topic_id` = '${id}'")->fetchColumn();

            // Пересчитываем топик
            $tools->recountForumTopic($id);

            // Обновляем статистику юзера
            $db->exec(
                "UPDATE `users` SET
                `postforum`='" . ($user->postforum + 1) . "',
                `lastpost` = '" . time() . "'
                WHERE `id` = '" . $user->id . "'
            "
            );

            // Вычисляем, на какую страницу попадает добавляемый пост
            if ($user->rights >= 7) {
                $page = $set_forum['upfp'] ? 1 : ceil($cnt_all_messages / $user->config->kmess);
            } else {
                $page = $set_forum['upfp'] ? 1 : ceil($cnt_messages / $user->config->kmess);
            }

            if (isset($_POST['addfiles'])) {
                $db->query(
                    "INSERT INTO `cms_forum_rdm` (topic_id,  user_id, `time`)
                VALUES ('${id}', '" . $user->id . "', '" . time() . "')
                ON DUPLICATE KEY UPDATE `time` = VALUES(`time`)"
                );
                if ($update) {
                    header('Location: ?type=topic&id=' . $res['id'] . '&act=addfile');
                } else {
                    header('Location: ?type=topic&id=' . $fadd . '&act=addfile');
                }
            } else {
                header('Location: ?type=topic&id=' . $id . '&page=' . $page);
            }
            exit;
        }
        $msg_pre = $tools->checkout($msg, 1, 1);
        $msg_pre = $tools->smilies($msg_pre, $user->rights ? 1 : 0);
        $msg_pre = preg_replace('#\[c\](.*?)\[/c\]#si', '<div class="quote">\1</div>', $msg_pre);

        $token = mt_rand(1000, 100000);
        $_SESSION['token'] = $token;

        return $this->render->render(
            'forum::reply_message',
            [
                'title'             => __('New message'),
                'page_title'        => __('New message'),
                'id'                => $topicId,
                'bbcode'            => di(\Johncms\System\Legacy\Bbcode::class)->buttons('message_form', 'msg'),
                'token'             => $token,
                'topic'             => $currentTopic,
                'form_action'       => '?act=say&amp;type=post&amp;id=' . $topicId . '&amp;start=',
                'add_file'          => isset($_POST['addfiles']),
                'msg'               => (empty($_POST['msg']) ? '' : $tools->checkout($msg, 0, 0)),
                'settings_forum'    => $set_forum,
                'show_post_preview' => ($msg && ! isset($_POST['submit'])),
                'back_url'          => $currentTopic->url,
                'preview_message'   => $msg_pre,
                'is_new_message'    => true,
            ]
        );
    }
}
