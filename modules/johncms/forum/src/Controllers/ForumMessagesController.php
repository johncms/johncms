<?php

declare(strict_types=1);

namespace Johncms\Forum\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Johncms\Forum\ForumPermissions;
use Johncms\Forum\ForumUtils;
use Johncms\Forum\Messages\ForumMessagesService;
use Johncms\Forum\Models\ForumMessage;
use Johncms\Forum\Models\ForumTopic;
use Johncms\Forum\Topics\ForumTopicService;
use Johncms\Http\Request;
use Johncms\Http\Response\RedirectResponse;
use Johncms\System\Legacy\Bbcode;
use Johncms\System\Legacy\Tools;
use Johncms\Users\User;
use Johncms\Users\UserManager;
use Psr\Http\Message\ResponseInterface;

class ForumMessagesController extends BaseForumController
{
    public function addMessage(int $topicId, User $user, Tools $tools, Request $request, ForumUtils $forumUtils, ForumTopicService $topicService): string|ResponseInterface
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

            // Проверяем, было ли последнее сообщение от того же автора?
            $update = false;
            if ($previousMessage) {
                $update = true;
                if (
                    ! isset($_POST['addfiles']) &&
                    $previousMessage->date + 3600 < strtotime('+ 1 hour') &&
                    empty($previousMessage->files_count)
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
                            'ip'           => ip2long($request->getIp()),
                            'ip_via_proxy' => ip2long($request->getIpViaProxy()),
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
            'forum::reply_message',
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

    public function deleteMessage(int $id, User $user): string
    {
        $this->metaTagManager->setAll(__('Delete Message'));
        $message = ForumMessage::query()->findOrFail($id);
        $countMessages = ForumMessage::query()->where('topic_id', $message->topic_id)->count();
        return $this->render->render(
            'forum::delete_post',
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

        return new RedirectResponse($message->topic->url);
    }
}
