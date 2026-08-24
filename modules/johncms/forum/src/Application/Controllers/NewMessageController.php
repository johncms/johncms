<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\CurrentUser;
use Johncms\Modules\Forum\Application\Services\ForumPermissions;
use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Exceptions\ForumNotFoundException;
use Johncms\Modules\Forum\Application\Services\ForumErrorRenderer;
use Johncms\Modules\Forum\Application\Services\ForumTopicPathService;
use Johncms\Modules\Forum\Application\UseCases\AttachUploadedFilesToMessageUseCase;
use Johncms\Modules\Forum\Application\UseCases\GetNewMessageContextUseCase;
use Johncms\Modules\Forum\Application\UseCases\PostMessageUseCase;
use Johncms\Modules\Forum\Domain\Repository\ForumMessageRepositoryInterface;
use Johncms\Security\AntifloodCheckerInterface;
use Johncms\Http\Environment;
use Johncms\Http\Request;
use Johncms\Http\View\ViewResponse;
use Johncms\Http\Session;
use Johncms\System\Utility\EditorContentNormalizer;
use Twig\Markup;

final readonly class NewMessageController
{
    public function __construct(
        private Environment $environment,
        private Session $session,
        private AntifloodCheckerInterface $antifloodChecker,
        private EditorContentNormalizer $editorContentNormalizer,
        private CurrentUser $currentUser,
        private ForumMessageRepositoryInterface $messageRepository,
        private ForumErrorRenderer $forumErrorRenderer,
        private GetNewMessageContextUseCase $contextUseCase,
        private PostMessageUseCase $postMessageUseCase,
        private AttachUploadedFilesToMessageUseCase $attachUploadedFilesUseCase,
        private ForumTopicPathService $topicPathService,
        private AccessCheckerInterface $accessChecker,
    ) {
    }

    public function __invoke(Request $request, int $id): ViewResponse
    {
        $page = max(1, $request->queryInt('page', 1));

        try {
            $topic = $this->contextUseCase->execute($id);
        } catch (ForumAccessDeniedException $exception) {
            return $this->forumErrorRenderer->viewResponse(
                $exception,
                [
                    'title'         => __('New message'),
                    'message'       => __('Access forbidden'),
                    'back_url'      => '/forum/',
                    'back_url_name' => __('Back'),
                ]
            );
        } catch (ForumNotFoundException) {
            pageNotFound();
        }

        if (
            ($topic->deleted && ! $this->accessChecker->allows(ForumPermissions::DELETED_VIEW))
            || ($topic->closed && ! $this->accessChecker->allows(ForumPermissions::TOPIC_MODERATE))
        ) {
            return new ViewResponse(
                '@theme/pages/result.twig',
                [
                    'title'         => __('New message'),
                    'type'          => 'alert-danger',
                    'message'       => __('You cannot write in a closed topic'),
                    'back_url'      => $topic->url,
                    'back_url_name' => __('Back'),
                ]
            );
        }

        $flood = $this->antifloodChecker->getRemainingSeconds();
        if ($flood) {
            return new ViewResponse(
                '@theme/pages/result.twig',
                [
                    'title'         => __('New message'),
                    'type'          => 'alert-danger',
                    'message'       => new Markup(
                        sprintf(__('You cannot add the message so often<br>Please, wait %d sec.'), $flood),
                        'UTF-8'
                    ),
                    'back_url'      => $this->buildTopicBackUrl($topic->url, $page),
                    'back_url_name' => __('Back'),
                ]
            );
        }

        $msg = $this->editorContentNormalizer->trimEdgeEmptyBlocks($request->body('msg', ''));
        $msg = trim($msg);
        $addFiles = $request->hasBody('addfiles');
        $attachedFiles = (array) $request->bodyInts('attached_files');

        if (
            $request->hasBody('submit')
            && $msg !== ''
            && $this->isValidToken($request)
        ) {
            if (mb_strlen($msg) < 4) {
                return new ViewResponse(
                    '@theme/pages/result.twig',
                    [
                        'title'         => __('New message'),
                        'type'          => 'alert-danger',
                        'message'       => __('Text is too short'),
                        'back_url'      => $topic->url,
                        'back_url_name' => __('Back'),
                    ]
                );
            }

            $lastMessage = $this->messageRepository->findLastMessageByUser($this->currentUser->id());
            if ($lastMessage !== null && $msg === (string) $lastMessage->getRawOriginal('text')) {
                return new ViewResponse(
                    '@theme/pages/result.twig',
                    [
                        'title'         => __('New message'),
                        'type'          => 'alert-danger',
                        'message'       => __('Message already exists'),
                        'back_url'      => $this->buildTopicBackUrl($topic->url, $page),
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
                forumSettings: $this->getForumSettings(),
                clientInfo: $this->environment->getClientInfo(),
            );
            $this->attachUploadedFilesUseCase->execute(
                messageId: $result->messageId,
                attachedFileIds: $attachedFiles,
            );

            if ($addFiles) {
                redirect('/forum/addfile/' . $result->messageId . '/');
            }

            redirect($this->topicPathService->getTopicUrlById($result->topicId, $result->page > 1 ? $result->page : null) ?? '/forum/');
        }

        $token = $this->regenerateToken();

        return new ViewResponse(
            '@forum/public/reply-message.twig',
            [
                'title'       => __('New message'),
                'page_title'  => __('New message'),
                'token'       => $token,
                'topic_name'  => $topic->name,
                'form_action' => '/forum/new-message/' . $topic->id . '/' . ($page > 1 ? '?page=' . $page : ''),
                'add_file'    => $addFiles,
                'msg'         => $msg,
                'back_url'    => $this->buildTopicBackUrl($topic->url, $page),
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
        if ($this->currentUser->isValid() && ! empty($this->currentUser->user()->set_forum)) {
            $setForum = (array) $this->currentUser->user()->set_forum;
        }

        return array_merge($setForumDefault, $setForum);
    }

    private function isValidToken(Request $request): bool
    {
        $token = $request->body('token', '');
        $sessionToken = (string) $this->session->get('token', '');

        return $token !== '' && $sessionToken !== '' && hash_equals($sessionToken, $token);
    }

    private function regenerateToken(): string
    {
        $token = (string) random_int(1000, 100000);
        $this->session->set('token', $token);

        return $token;
    }

    private function buildTopicBackUrl(string $topicUrl, int $page): string
    {
        if ($page <= 1) {
            return $topicUrl;
        }

        return $topicUrl . '?page=' . $page;
    }
}
