<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Forum\Application\Exceptions\AccessDeniedException;
use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Exceptions\NewMessageTopicNotFoundException;
use Johncms\Modules\Forum\Application\Services\ForumAccessResponseBuilder;
use Johncms\Modules\Forum\Application\Services\ForumTopicPathService;
use Johncms\Modules\Forum\Application\UseCases\EnsureForumAccessUseCase;
use Johncms\Modules\Forum\Application\UseCases\EnsurePostMessageAccessUseCase;
use Johncms\Modules\Forum\Application\UseCases\GetNewMessageContextUseCase;
use Johncms\Modules\Forum\Application\UseCases\PostMessageUseCase;
use Johncms\Modules\Forum\Domain\Repository\ForumMessageRepositoryInterface;
use Johncms\System\Http\Request;
use Johncms\System\Http\Session;
use Johncms\System\Legacy\Tools;
use Johncms\System\View\Render;
use Johncms\Users\User;
use Simba77\EmbedMedia\Embed;

final readonly class NewMessageController
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
        private GetNewMessageContextUseCase $contextUseCase,
        private PostMessageUseCase $postMessageUseCase,
        private ForumTopicPathService $topicPathService,
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
        } catch (NewMessageTopicNotFoundException) {
            pageNotFound();
        }

        $topic = $context->topic;

        if (($topic->deleted || $topic->closed) && $this->currentUser->rights < 7) {
            return $this->render->render(
                'system::pages/result',
                [
                    'title'         => __('New message'),
                    'type'          => 'alert-danger',
                    'message'       => __('You cannot write in a closed topic'),
                    'back_url'      => $topic->url,
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
                    'back_url'      => $topic->url . ($start > 0 ? '?start=' . $start : ''),
                    'back_url_name' => __('Back'),
                ]
            );
        }

        $msg = trim((string) $this->request->getPost('msg', ''));
        $addFiles = $this->request->getPost('addfiles') !== null;

        if (
            $this->request->getPost('submit') !== null
            && $msg !== ''
            && $this->isValidToken()
        ) {
            if (mb_strlen($msg) < 4) {
                return $this->render->render(
                    'system::pages/result',
                    [
                        'title'         => __('New message'),
                        'type'          => 'alert-danger',
                        'message'       => __('Text is too short'),
                        'back_url'      => $topic->url,
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
                        'back_url'      => $topic->url . ($start > 0 ? '?start=' . $start : ''),
                        'back_url_name' => __('Back'),
                    ]
                );
            }

            if ($this->session->get('fsort_id') === $topic->id) {
                $this->session->remove(['fsort_id', 'fsort_users']);
            }

            $this->session->remove('token');

            $result = $this->postMessageUseCase->execute(
                topic: $topic,
                messageText: $msg,
                addFiles: $addFiles,
                forumSettings: $this->getForumSettings()
            );

            if ($addFiles) {
                redirect('/forum/addfile/' . $result->messageId . '/');
            }

            redirect($this->topicPathService->getTopicUrlById($result->topicId, $result->page > 1 ? $result->page : null) ?? '/forum/');
        }

        $token = $this->regenerateToken();

        $msgPreview = $this->purifier->purify($msg);
        $msgPreview = $this->embed->embedMedia($msgPreview);
        $msgPreview = $this->tools->smilies($msgPreview, $this->currentUser->rights > 0);

        return $this->render->render(
            'forum::reply_message',
            [
                'title'             => __('New message'),
                'page_title'        => __('New message'),
                'id'                => $topic->id,
                'token'             => $token,
                'topic'             => $topic,
                'form_action'       => '/forum/new-message/' . $topic->id . '/?start=' . $start,
                'add_file'          => $addFiles,
                'msg'               => $msg === '' ? '' : $this->tools->checkout($msg, 0, 0),
                'settings_forum'    => $this->getForumSettings(),
                'show_post_preview' => ($msg !== '' && $this->request->getPost('submit') === null),
                'back_url'          => $topic->url . ($start > 0 ? '?start=' . $start : ''),
                'preview_message'   => $msgPreview,
                'is_new_message'    => true,
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
}
