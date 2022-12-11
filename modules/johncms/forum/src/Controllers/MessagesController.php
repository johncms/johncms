<?php

declare(strict_types=1);

namespace Johncms\Forum\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Johncms\Forum\ForumPermissions;
use Johncms\Forum\ForumUtils;
use Johncms\Forum\Services\ForumMessagesService;
use Johncms\Forum\Models\ForumMessage;
use Johncms\Forum\Models\ForumTopic;
use Johncms\Forum\Services\ForumTopicService;
use Johncms\Http\Request;
use Johncms\Http\Response\RedirectResponse;
use Johncms\Notifications\Notification;
use Johncms\System\Legacy\Bbcode;
use Johncms\System\Legacy\Tools;
use Johncms\Users\User;
use Johncms\Users\UserManager;
use Psr\Http\Message\ResponseInterface;

class MessagesController extends BaseForumController
{
    public function create(int $topicId, User $user, Tools $tools, Request $request, ForumUtils $forumUtils, ForumTopicService $topicService): string | ResponseInterface
    {
        $set_forum = [
            'farea'    => 0,
            'upfp'     => 0,
            'preview'  => 1,
            'postclip' => 1,
            'postcut'  => 2,
        ];

        $this->metaTagManager->setAll(__('New message'));
        $currentTopic = ForumTopic::query()->findOrFail($topicId);

        $forumUtils->buildBreadcrumbs($currentTopic->section_id, $currentTopic->name, $currentTopic->url);
        $this->navChain->add(__('New message'));

        // Check if the topic is closed or deleted
        if (($currentTopic->deleted || $currentTopic->closed) && ! $user->hasAnyRole()) {
            return $this->render->render(
                'system::pages/result',
                [
                    'title'         => __('New message'),
                    'type'          => 'alert-danger',
                    'message'       => __('You cannot write in a closed topic'),
                    'back_url'      => $currentTopic->url,
                    'back_url_name' => __('Back'),
                ]
            );
        }

        $msg = trim($request->getPost('msg', ''));
        // Replace links to forum topics with their names
        $msg = preg_replace_callback(
            '~\\[url=(http://.+?)\\](.+?)\\[/url\\]|(http://(www.)?[0-9a-zA-Z\.-]+\.[0-9a-zA-Z]{2,6}[0-9a-zA-Z/\?\.\~&amp;_=/%-:#]*)~',
            '\Johncms\Forum\ForumUtils::forumLink',
            $msg
        );

        if (isset($_POST['submit']) && ! empty($_POST['msg'])) {
            // Check min length of the message
            if (mb_strlen($msg) < 4) {
                return $this->render->render(
                    'system::pages/result',
                    [
                        'title'         => __('New message'),
                        'type'          => 'alert-danger',
                        'message'       => __('Text is too short'),
                        'back_url'      => $currentTopic->url,
                        'back_url_name' => __('Back'),
                    ]
                );
            }

            // Find a duplicate of the message
            $previousMessage = ForumMessage::query()
                ->withCount('files')
                ->where('user_id', $user->id)
                ->when(! $user->hasPermission(ForumPermissions::MANAGE_POSTS), function (Builder $builder) {
                    return $builder->visible();
                })
                ->orderByDesc('date')
                ->first();
            if ($previousMessage) {
                if ($msg == $previousMessage->text) {
                    return $this->render->render(
                        'system::pages/result',
                        [
                            'title'         => __('New message'),
                            'type'          => 'alert-danger',
                            'message'       => __('Message already exists'),
                            'back_url'      => $currentTopic->last_page_url,
                            'back_url_name' => __('Back'),
                        ]
                    );
                }
            }

            // Удаляем фильтр, если он был
            if (isset($_SESSION['fsort_id']) && $_SESSION['fsort_id'] == $topicId) {
                unset($_SESSION['fsort_id'], $_SESSION['fsort_users']);
            }

            // Check the previous message
            $previousMessage = ForumMessage::query()
                ->withCount('files')
                ->where('topic_id', $topicId)
                ->when(! $user->hasPermission(ForumPermissions::MANAGE_POSTS), function (Builder $builder) {
                    return $builder->visible();
                })
                ->orderByDesc('date')
                ->first();

            $update = false;
            if ($previousMessage) {
                $update = true;
                if (
                    ! isset($_POST['addfiles'])
                    && $previousMessage->date + 3600 < (time() + 3600)
                    && $previousMessage->user_id === $user->id
                    && empty($previousMessage->files_count)
                ) {
                    $newText = $previousMessage->text;
                    if (! str_contains($newText, '[timestamp]')) {
                        $newText = '[timestamp]' . date('d.m.Y H:i', $previousMessage->date) . '[/timestamp]' . PHP_EOL . $newText;
                    }
                    $newText .= PHP_EOL . PHP_EOL . '[timestamp]' . date('d.m.Y H:i', time()) . '[/timestamp]' . PHP_EOL . $msg;

                    // Обновляем пост
                    $previousMessage->update(['text' => $newText, 'date' => time()]);
                } else {
                    $update = false;
                    // Insert the message to database
                    $createdMessage = ForumMessage::query()->create(
                        [
                            'topic_id'     => $topicId,
                            'date'         => time(),
                            'user_id'      => $user->id,
                            'user_name'    => $user->display_name,
                            'ip'           => $request->getIp(),
                            'ip_via_proxy' => $request->getIpViaProxy(),
                            'user_agent'   => $request->getUserAgent(),
                            'text'         => $msg,
                        ]
                    );
                }
            }

            // Пересчитываем топик
            $tools->recountForumTopic($topicId);

            // Update user activity
            $userManager = di(UserManager::class);
            $userManager->incrementActivity($user, 'forum_posts');

            if ($request->getPost('addfiles')) {
                $topicService->markAsRead($topicId, $user->id);
                $messageId = $update ? $previousMessage->id : ($createdMessage?->id ?? 0);
                return new RedirectResponse(route('forum.addFile', ['messageId' => $messageId]));
            } else {
                return new RedirectResponse($currentTopic->last_page_url);
            }
        }

        return $this->render->render(
            'johncms/forum::reply_message',
            [
                'id'             => $topicId,
                'bbcode'         => di(Bbcode::class)->buttons('message_form', 'msg'),
                'topic'          => $currentTopic,
                'form_action'    => route('forum.addMessage', ['topicId' => $topicId]),
                'add_file'       => isset($_POST['addfiles']),
                'msg'            => (empty($_POST['msg']) ? '' : $tools->checkout($msg, 0, 0)),
                'settings_forum' => $set_forum,
                'back_url'       => $currentTopic->url,
                'is_new_message' => true,
            ]
        );
    }

    public function delete(int $id, User $user): string
    {
        $this->metaTagManager->setAll(__('Delete Message'));
        $message = ForumMessage::query()->findOrFail($id);
        $countMessages = ForumMessage::query()->where('topic_id', $message->topic_id)->count();
        return $this->render->render(
            'johncms/forum::delete_post',
            [
                'id'            => $id,
                'actionUrl'     => route('forum.confirmDeletePost', ['id' => $id]),
                'countMessages' => $countMessages,
                'backUrl'       => $message->topic->url,
                'canHide'       => $user->hasPermission(ForumPermissions::MANAGE_POSTS),
            ]
        );
    }

    public function confirmDelete(int $id, User $user, Request $request, ForumMessagesService $messagesService): ResponseInterface
    {
        $actionType = $request->getPost('type', 'delete');
        $message = ForumMessage::query()->findOrFail($id);
        if ($actionType === 'delete' && ! $user->hasPermission(ForumPermissions::MANAGE_POSTS)) {
            $actionType = 'hide';
        }

        if ($actionType === 'delete') {
            $messagesService->delete($message);
        } elseif ($actionType === 'hide') {
            $messagesService->hide($message);
        }

        if ($message->topic->deleted && ! $user->hasPermission(ForumPermissions::MANAGE_POSTS)) {
            return new RedirectResponse($message->topic->section->url);
        }

        return new RedirectResponse($message->topic->url);
    }

    public function restore(int $id, Tools $tools, ForumMessagesService $messagesService): RedirectResponse
    {
        $message = ForumMessage::query()->findOrFail($id);
        $messagesService->restore($message);
        $tools->recountForumTopic($message->topic_id);
        return new RedirectResponse($message->topic->last_page_url);
    }

    public function edit(int $id, Tools $tools, User $user): RedirectResponse | string
    {
        $error = false;
        $message = ForumMessage::query()->findOrFail($id);
        $topic = $message->topic;

        if (! $user->hasPermission(ForumPermissions::MANAGE_POSTS) && ! array_key_exists($user->id, $topic->curators)) {
            if ($message->user_id != $user->id) {
                $error = __('You are trying to change another\'s post') . '<br /><a href="' . $topic->last_page_url . '">' . __('Back') . '</a>';
            }
            if (! $error) {
                $section = $topic->section;
                $allow = (int) $section->access;
                $check = true;
                if ($allow == 2) {
                    $firstMessage = ForumMessage::query()->where('topic_id', $topic->id)->orderBy('id')->first();
                    if ($firstMessage->user_id == $user->id && $firstMessage->id == $id) {
                        $check = false;
                    }
                }

                if ($check && $message->date < time() - 3600) {
                    $error = __('You cannot edit your posts after %s minutes', 60) . '<br /><a href="' . $topic->last_page_url . '">' . __('Back') . '</a>';
                }
            }
        }

        if ($error) {
            return $this->render->render(
                'system::pages/result',
                [
                    'title'   => __('Error'),
                    'type'    => 'alert-danger',
                    'message' => $error,
                ]
            );
        }

        $msg = isset($_POST['msg']) ? trim($_POST['msg']) : '';

        if (isset($_POST['submit'])) {
            if (empty($_POST['msg'])) {
                return $this->render->render(
                    'system::pages/result',
                    [
                        'title'         => __('Edit Message'),
                        'type'          => 'alert-danger',
                        'message'       => __('You have not entered the message'),
                        'back_url'      => route('forum.editMessage', ['id' => $id]),
                        'back_url_name' => __('Repeat'),
                    ]
                );
            }

            $message->update(
                [
                    'text'        => $msg,
                    'edit_time'   => time(),
                    'editor_name' => $user->display_name,
                    'edit_count'  => $message->edit_count + 1,
                ]
            );

            return new RedirectResponse($topic->last_page_url);
        }

        $message = (empty($_POST['msg']) ? htmlentities($message->text, ENT_QUOTES, 'UTF-8') : $tools->checkout($_POST['msg']));

        $this->metaTagManager->setAll(__('Edit Message'));

        return $this->render->render(
            'johncms/forum::edit_post',
            [
                'id'        => $id,
                'bbcode'    => di(Bbcode::class)->buttons('edit_post', 'msg'),
                'msg'       => $message,
                'back_url'  => $topic->last_page_url,
                'actionUrl' => route('forum.editMessage', ['id' => $id]),
            ]
        );
    }

    public function reply(int $id, User $user, Request $request, Tools $tools): RedirectResponse | string
    {
        $message = ForumMessage::query()->findOrFail($id);
        $topic = $message->topic;
        $isQuote = $request->getQuery('quote', false);

        if (($topic->deleted || $topic->closed) && ! $user->hasPermission(ForumPermissions::MANAGE_POSTS)) {
            return $this->render->render(
                'system::pages/result',
                [
                    'title'         => __('New message'),
                    'type'          => 'alert-danger',
                    'message'       => __('You cannot write in a closed topic'),
                    'back_url'      => $topic->last_page_url,
                    'back_url_name' => __('Back'),
                ]
            );
        }

        if ($message->user_id === $user->id) {
            return $this->render->render(
                'system::pages/result',
                [
                    'title'         => __('New message'),
                    'type'          => 'alert-danger',
                    'message'       => __('You can not reply to your own message'),
                    'back_url'      => $topic->last_page_url,
                    'back_url_name' => __('Back'),
                ]
            );
        }

        $msg = isset($_POST['msg']) ? trim($_POST['msg']) : '';
        $txt = isset($_POST['txt']) ? (int) ($_POST['txt']) : false;

        $msg = preg_replace_callback(
            '~\\[url=(http://.+?)\\](.+?)\\[/url\\]|(http://(www.)?[0-9a-zA-Z\.-]+\.[0-9a-zA-Z]{2,6}[0-9a-zA-Z/\?\.\~&amp;_=/%-:#]*)~',
            '\Johncms\Forum\ForumUtils::forumLink',
            $msg
        );

        if ($request->isPost()) {
            if (empty($_POST['msg'])) {
                return $this->render->render(
                    'system::pages/result',
                    [
                        'title'         => __('New message'),
                        'type'          => 'alert-danger',
                        'message'       => __('You have not entered the message'),
                        'back_url'      => route('forum.reply', ['id' => $id], ['quote' => $request->getQuery('quote')]),
                        'back_url_name' => __('Repeat'),
                    ]
                );
            }

            // Проверяем на минимальную длину
            if (mb_strlen($msg) < 4) {
                return $this->render->render(
                    'system::pages/result',
                    [
                        'title'         => __('New message'),
                        'type'          => 'alert-danger',
                        'message'       => __('Text is too short'),
                        'back_url'      => route('forum.reply', ['id' => $id], ['quote' => $request->getQuery('quote')]),
                        'back_url_name' => __('Back'),
                    ]
                );
            }

            // Check if the message is not repeated
            $repeatMessage = ForumMessage::query()->where('user_id', $user->id)->orderByDesc('date')->first();
            if ($repeatMessage) {
                if ($msg == $repeatMessage->text) {
                    return $this->render->render(
                        'system::pages/result',
                        [
                            'title'         => __('New message'),
                            'type'          => 'alert-danger',
                            'message'       => __('Message already exists'),
                            'back_url'      => $repeatMessage->topic->last_page_url,
                            'back_url_name' => __('Back'),
                        ]
                    );
                }
            }

            if (isset($_SESSION['fsort_id']) && $_SESSION['fsort_id'] == $repeatMessage->topic_id) {
                unset($_SESSION['fsort_id'], $_SESSION['fsort_users']);
            }

            $createdMessage = ForumMessage::query()->create(
                [
                    'topic_id'     => $message->topic_id,
                    'date'         => time(),
                    'user_id'      => $user->id,
                    'user_name'    => $user->display_name,
                    'ip'           => $request->getIp(),
                    'ip_via_proxy' => $request->getIpViaProxy(),
                    'user_agent'   => $request->getUserAgent(),
                    'text'         => $msg,
                ]
            );

            // Update user activity
            $userManager = di(UserManager::class);
            $userManager->incrementActivity($user, 'forum_posts');

            $tools->recountForumTopic($message->topic_id);

            // Добавляем уведомление об ответе
            $preview_message = strip_tags($tools->checkout(trim($_POST['msg']), 1, 1));
            $preview_message = strlen($preview_message) > 200 ? mb_substr($preview_message, 0, 200) . '...' : $preview_message;
            $preview_message = $tools->smilies($preview_message, $user->hasAnyRole());
            (new Notification())->create(
                [
                    'module'     => 'forum',
                    'event_type' => 'new_message',
                    'user_id'    => $message->user_id,
                    'sender_id'  => $user->id,
                    'entity_id'  => $createdMessage->id,
                    'fields'     => [
                        'topic_name'       => htmlspecialchars($topic->name),
                        'user_name'        => htmlspecialchars($user->name),
                        'topic_url'        => $topic->last_page_url,
                        'reply_to_message' => '/forum/?act=show_post&id=' . $message->id,
                        'message'          => $preview_message,
                        'post_id'          => $createdMessage->id,
                        'topic_id'         => $topic->id,
                    ],
                ]
            );

            if (isset($_POST['addfiles'])) {
                return new RedirectResponse(route('forum.addFile', ['messageId' => $createdMessage->id]));
            }
            return new RedirectResponse($topic->last_page_url);
        } else {
            $msg = $message->user_name . ', ';
            if ($isQuote) {
                $tp = date('d.m.Y H:i', $message->date);
                $msg = '[quote][url=' . $message->user->profile_url . ']' . $message->user_name . '[/url]'
                    . ' ([time]' . $tp . "[/time])\n" . $tools->checkout($message->text, 1, 2) . '[/quote]' . "\r\n\r\n";
            }
        }

        $this->metaTagManager->setTitle(__('Reply to message'));

        return $this->render->render(
            'johncms/forum::reply_message',
            [
                'id'          => $id,
                'bbcode'      => di(Bbcode::class)->buttons('message_form', 'msg'),
                'topicName'   => $topic->name,
                'form_action' => route('forum.reply', ['id' => $id], ['quote' => $request->getQuery('quote')]),
                'txt'         => $txt ?? null,
                'is_quote'    => isset($_GET['cyt']),
                'add_file'    => isset($_POST['addfiles']),
                'msg'         => $request->isPost() ? $tools->checkout($msg) : $msg,
                'back_url'    => $topic->last_page_url,
            ]
        );
    }
}
