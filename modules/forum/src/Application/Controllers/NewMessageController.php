<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Exceptions\ForumNotFoundException;
use Johncms\Modules\Forum\Application\Services\ForumErrorRenderer;
use Johncms\Modules\Forum\Application\Services\ForumTopicPathService;
use Johncms\Modules\Forum\Application\UseCases\AttachUploadedFilesToMessageUseCase;
use Johncms\Modules\Forum\Application\UseCases\GetNewMessageContextUseCase;
use Johncms\Modules\Forum\Application\UseCases\PostMessageUseCase;
use Johncms\Modules\Forum\Domain\Repository\ForumMessageRepositoryInterface;
use Johncms\Security\AntifloodCheckerInterface;
use Johncms\Smilies\SmiliesRendererInterface;
use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\System\Utility\EditorContentNormalizer;
use Johncms\System\View\Render;
use Johncms\Users\User;
use Simba77\EmbedMedia\Embed;
use Symfony\Component\HttpFoundation\Response;

final readonly class NewMessageController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private Session $session,
        private AntifloodCheckerInterface $antifloodChecker,
        private SmiliesRendererInterface $smiliesRenderer,
        private EditorContentNormalizer $editorContentNormalizer,
        private \HTMLPurifier $purifier,
        private Embed $embed,
        private User $currentUser,
        private ForumMessageRepositoryInterface $messageRepository,
        private ForumErrorRenderer $forumErrorRenderer,
        private GetNewMessageContextUseCase $contextUseCase,
        private PostMessageUseCase $postMessageUseCase,
        private AttachUploadedFilesToMessageUseCase $attachUploadedFilesUseCase,
        private ForumTopicPathService $topicPathService,
    ) {
        $this->controllerContext->initModule('forum');
    }

    public function __invoke(int $id): Response
    {
        $page = max(1, $this->request->queryInt('page', 1));

        try {
            $topic = $this->contextUseCase->execute($id);
        } catch (ForumAccessDeniedException $exception) {
            return $this->forumErrorRenderer->render(
                $this->render,
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

        if (($topic->deleted || $topic->closed) && $this->currentUser->rights < 7) {
            return new Response(
                $this->render->render(
                    'system::pages/result',
                    [
                        'title'         => __('New message'),
                        'type'          => 'alert-danger',
                        'message'       => __('You cannot write in a closed topic'),
                        'back_url'      => $topic->url,
                        'back_url_name' => __('Back'),
                    ]
                )
            );
        }

        $flood = $this->antifloodChecker->getRemainingSeconds();
        if ($flood) {
            return new Response(
                $this->render->render(
                    'system::pages/result',
                    [
                        'title'         => __('New message'),
                        'type'          => 'alert-danger',
                        'message'       => sprintf(__('You cannot add the message so often<br>Please, wait %d sec.'), $flood),
                        'back_url'      => $this->buildTopicBackUrl($topic->url, $page),
                        'back_url_name' => __('Back'),
                    ]
                )
            );
        }

        $msg = $this->editorContentNormalizer->trimEdgeEmptyBlocks($this->request->body('msg', ''));
        $msg = trim($msg);
        $addFiles = $this->request->hasBody('addfiles');
        $attachedFiles = (array) $this->request->bodyInts('attached_files');

        if (
            $this->request->hasBody('submit')
            && $msg !== ''
            && $this->isValidToken()
        ) {
            if (mb_strlen($msg) < 4) {
                return new Response(
                    $this->render->render(
                        'system::pages/result',
                        [
                            'title'         => __('New message'),
                            'type'          => 'alert-danger',
                            'message'       => __('Text is too short'),
                            'back_url'      => $topic->url,
                            'back_url_name' => __('Back'),
                        ]
                    )
                );
            }

            $lastMessage = $this->messageRepository->findLastMessageByUser($this->currentUser->id);
            if ($lastMessage !== null && $msg === (string) $lastMessage->getRawOriginal('text')) {
                return new Response(
                    $this->render->render(
                        'system::pages/result',
                        [
                            'title'         => __('New message'),
                            'type'          => 'alert-danger',
                            'message'       => __('Message already exists'),
                            'back_url'      => $this->buildTopicBackUrl($topic->url, $page),
                            'back_url_name' => __('Back'),
                        ]
                    )
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

        $msgPreview = $this->purifier->purify($msg);
        $msgPreview = $this->embed->embedMedia($msgPreview);
        $msgPreview = $this->smiliesRenderer->render($msgPreview, $this->currentUser->rights > 0);

        return new Response(
            $this->render->render(
                'forum::reply_message',
                [
                    'title'             => __('New message'),
                    'page_title'        => __('New message'),
                    'id'                => $topic->id,
                    'token'             => $token,
                    'topic'             => $topic,
                    'form_action'       => '/forum/new-message/' . $topic->id . '/' . ($page > 1 ? '?page=' . $page : ''),
                    'add_file'          => $addFiles,
                    'msg'               => $msg,
                    'settings_forum'    => $this->getForumSettings(),
                    'show_post_preview' => ($msg !== '' && ! $this->request->hasBody('submit')),
                    'back_url'          => $this->buildTopicBackUrl($topic->url, $page),
                    'preview_message'   => $msgPreview,
                    'is_new_message'    => true,
                ]
            )
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
        $token = $this->request->body('token', '');
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
