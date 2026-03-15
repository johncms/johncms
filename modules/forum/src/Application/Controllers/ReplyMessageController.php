<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Forum\Application\Exceptions\AccessDeniedException;
use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Exceptions\ReplyMessageNotFoundException;
use Johncms\Modules\Forum\Application\Services\ForumAccessResponseBuilder;
use Johncms\Modules\Forum\Application\UseCases\EnsureForumAccessUseCase;
use Johncms\Modules\Forum\Application\UseCases\EnsurePostMessageAccessUseCase;
use Johncms\Modules\Forum\Application\UseCases\GetReplyMessageContextUseCase;
use Johncms\Modules\Forum\Application\UseCases\ReplyMessageUseCase;
use Johncms\Modules\Forum\Domain\Repository\ForumMessageRepositoryInterface;
use Johncms\System\Http\Request;
use Johncms\System\Http\Session;
use Johncms\System\Legacy\Tools;
use Johncms\System\View\Render;
use Johncms\Users\User;
use Simba77\EmbedMedia\Embed;

final readonly class ReplyMessageController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private Session $session,
        private Tools $tools,
        private \HTMLPurifier $purifier,
        private Embed $embed,
        private User $currentUser,
        private ForumMessageRepositoryInterface $messageRepository,
        private EnsureForumAccessUseCase $forumAccessUseCase,
        private ForumAccessResponseBuilder $forumAccessResponseBuilder,
        private EnsurePostMessageAccessUseCase $accessUseCase,
        private GetReplyMessageContextUseCase $contextUseCase,
        private ReplyMessageUseCase $replyMessageUseCase,
    ) {
        $this->controllerContext->initModule('forum');
    }

    public function __invoke(int $id): string
    {
        $start = (int) $this->request->getQuery('start', 0);

        try {
            $this->forumAccessUseCase->execute();
        } catch (ForumAccessDeniedException $exception) {
            return $this->render->render(
                'system::pages/result',
                $this->forumAccessResponseBuilder->forException($exception)
            );
        }

        try {
            $this->accessUseCase->execute();
        } catch (AccessDeniedException) {
            http_response_code(403);
            return $this->render->render(
                'system::pages/result',
                [
                    'title'         => __('New message'),
                    'type'          => 'alert-danger',
                    'message'       => __('Access forbidden'),
                    'back_url'      => '/forum/',
                    'back_url_name' => __('Back'),
                ]
            );
        }

        try {
            $context = $this->contextUseCase->execute($id);
        } catch (ReplyMessageNotFoundException) {
            pageNotFound();
        }

        $topic = $context->topic;
        $sourceMessage = $context->message;

        if (($topic->deleted || $topic->closed) && $this->currentUser->rights < 7) {
            return $this->render->render(
                'system::pages/result',
                [
                    'title'         => __('New message'),
                    'type'          => 'alert-danger',
                    'message'       => __('You cannot write in a closed topic'),
                    'back_url'      => '/forum/?type=topic&id=' . $topic->id,
                    'back_url_name' => __('Back'),
                ]
            );
        }

        if ($sourceMessage->user_id === $this->currentUser->id) {
            return $this->render->render(
                'system::pages/result',
                [
                    'title'         => __('New message'),
                    'type'          => 'alert-danger',
                    'message'       => __('You can not reply to your own message'),
                    'back_url'      => '/forum/?type=topic&id=' . $topic->id,
                    'back_url_name' => __('Back'),
                ]
            );
        }

        $flood = $this->tools->antiflood();
        if ($flood) {
            return $this->render->render(
                'system::pages/result',
                [
                    'title'         => __('New message'),
                    'type'          => 'alert-danger',
                    'message'       => sprintf(__('You cannot add the message so often<br>Please, wait %d sec.'), $flood),
                    'back_url'      => '/forum/?type=topic&id=' . $topic->id . '&amp;start=' . $start,
                    'back_url_name' => __('Back'),
                ]
            );
        }

        $msg = trim((string) $this->request->getPost('msg', ''));
        $addFiles = $this->request->getPost('addfiles') !== null;

        if (
            $this->request->getPost('submit') !== null
            && $this->isValidToken()
        ) {
            if ($msg === '') {
                return $this->render->render(
                    'system::pages/result',
                    [
                        'title'         => __('New message'),
                        'type'          => 'alert-danger',
                        'message'       => __('You have not entered the message'),
                        'back_url'      => $this->getReplyUrl($id),
                        'back_url_name' => __('Repeat'),
                    ]
                );
            }

            if (mb_strlen($msg) < 4) {
                return $this->render->render(
                    'system::pages/result',
                    [
                        'title'         => __('New message'),
                        'type'          => 'alert-danger',
                        'message'       => __('Text is too short'),
                        'back_url'      => '/forum/?type=topic&id=' . $topic->id,
                        'back_url_name' => __('Back'),
                    ]
                );
            }

            $lastMessage = $this->messageRepository->findLastMessageByUser($this->currentUser->id);
            if ($lastMessage !== null && $msg === (string) $lastMessage->getRawOriginal('text')) {
                return $this->render->render(
                    'system::pages/result',
                    [
                        'title'         => __('New message'),
                        'type'          => 'alert-danger',
                        'message'       => __('Message already exists'),
                        'back_url'      => '/forum/?type=topic&id=' . $topic->id . '&amp;start=' . $start,
                        'back_url_name' => __('Back'),
                    ]
                );
            }

            if ($this->session->get('fsort_id') === $topic->id) {
                $this->session->remove(['fsort_id', 'fsort_users']);
            }

            $this->session->remove('token');

            $result = $this->replyMessageUseCase->execute(
                sourceMessage: $sourceMessage,
                topic: $topic,
                messageText: $msg,
                addFiles: $addFiles,
                forumSettings: $this->getForumSettings()
            );

            if ($addFiles) {
                redirect('/forum/addfile/' . $result->messageId . '/');
            }

            redirect('/forum/?type=topic&id=' . $result->topicId . '&page=' . $result->page);
        }

        $token = $this->regenerateToken();

        $isQuote = $this->request->getQuery('quote') !== null;
        $quoteText = (string) $sourceMessage->getRawOriginal('text');

        if ($isQuote) {
            $msg = '<blockquote>' . $quoteText . '</blockquote><p>' . $msg . '</p>';
        } else {
            $msg = '<p>' . $sourceMessage->user_name . ',&nbsp;' . $msg . '</p>';
        }

        $msgPreview = $this->purifier->purify($msg);
        $msgPreview = $this->embed->embedMedia($msgPreview);
        $msgPreview = $this->tools->smilies($msgPreview, $this->currentUser->rights > 0);

        return $this->render->render(
            'forum::reply_message',
            [
                'title'             => __('Reply to message'),
                'page_title'        => __('Reply to message'),
                'id'                => $sourceMessage->id,
                'token'             => $token,
                'topic'             => $topic,
                'form_action'       => $this->getReplyUrl($sourceMessage->id, $start),
                'is_quote'          => $isQuote,
                'add_file'          => $addFiles,
                'msg'               => $msg === '' ? '' : $this->tools->checkout($msg, 0, 0),
                'message'           => $sourceMessage,
                'settings_forum'    => $this->getForumSettings(),
                'show_post_preview' => ($this->request->getPost('submit') === null && $this->request->getPost('msg') !== null),
                'back_url'          => '/forum/?type=topic&id=' . $topic->id . '&amp;start=' . $start,
                'is_new_message'    => false,
                'preview_message'   => $msgPreview,
            ]
        );
    }

    private function getForumSettings(): array
    {
        $setForumDefault = [
            'farea'    => 0,
            'upfp'     => 0,
            'preview'  => 1,
            'postclip' => 1,
            'postcut'  => 2,
        ];

        $setForum = [];
        if ($this->currentUser->isValid() && ! empty($this->currentUser->set_forum)) {
            $setForum = (array) $this->currentUser->set_forum;
        }

        return array_merge($setForumDefault, $setForum);
    }

    private function isValidToken(): bool
    {
        $token = (string) $this->request->getPost('token', '');
        $sessionToken = (string) $this->session->get('token', '');

        return $token !== '' && $sessionToken !== '' && hash_equals($sessionToken, $token);
    }

    private function regenerateToken(): string
    {
        $token = (string) random_int(1000, 100000);
        $this->session->set('token', $token);

        return $token;
    }

    private function getReplyUrl(int $messageId, int $start = 0): string
    {
        $url = '/forum/reply-message/' . $messageId . '/';
        if ($start > 0) {
            $url .= '?start=' . $start;
        }
        if ($this->request->getQuery('quote') !== null) {
            $url .= ($start > 0 ? '&amp;' : '?') . 'quote=1';
        }

        return $url;
    }
}
