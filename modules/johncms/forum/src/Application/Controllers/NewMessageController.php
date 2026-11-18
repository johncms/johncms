<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\CurrentUser;
use Johncms\Database\TransactionManagerInterface;
use Johncms\EditorUploads\EditorUploadBindException;
use Johncms\Http\Environment;
use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\Http\UploadedFileMapper;
use Johncms\Http\View\ViewResponse;
use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Exceptions\ForumNotFoundException;
use Johncms\Modules\Forum\Application\ForumUtils;
use Johncms\Modules\Forum\Application\Services\ForumAnsweredPosts;
use Johncms\Modules\Forum\Application\Services\ForumErrorRenderer;
use Johncms\Modules\Forum\Application\Services\ForumFormTokens;
use Johncms\Modules\Forum\Application\Services\ForumPermissions;
use Johncms\Modules\Forum\Application\Services\ForumPostChecks;
use Johncms\Modules\Forum\Application\Services\ForumTopicPathService;
use Johncms\Modules\Forum\Application\Uploads\ForumMessagePictures;
use Johncms\Modules\Forum\Application\Uploads\ForumPostFiles;
use Johncms\Modules\Forum\Application\UseCases\GetNewMessageContextUseCase;
use Johncms\Modules\Forum\Application\UseCases\PostMessageUseCase;
use Johncms\Modules\Forum\Application\UseCases\ReplyMessageUseCase;
use Johncms\Modules\Forum\Domain\Models\ForumTopic;
use Johncms\NavChain;
use Johncms\Storage\StorageException;
use Johncms\System\Utility\EditorContentNormalizer;
use Symfony\Component\HttpFoundation\Response;

/**
 * A post in a topic. The form under the topic sends here; the page of this address is that form
 * on its own, for the way without the script and for a form sent back with what was wrong.
 */
final readonly class NewMessageController
{
    public function __construct(
        private Environment $environment,
        private Session $session,
        private EditorContentNormalizer $editorContentNormalizer,
        private CurrentUser $currentUser,
        private ForumErrorRenderer $forumErrorRenderer,
        private GetNewMessageContextUseCase $contextUseCase,
        private PostMessageUseCase $postMessageUseCase,
        private ReplyMessageUseCase $replyMessageUseCase,
        private ForumAnsweredPosts $answeredPosts,
        private ForumMessagePictures $pictures,
        private TransactionManagerInterface $transactions,
        private ForumTopicPathService $topicPathService,
        private AccessCheckerInterface $accessChecker,
        private ForumFormTokens $formTokens,
        private ForumPostFiles $postFiles,
        private ForumPostChecks $checks,
        private UploadedFileMapper $uploadedFiles,
        private NavChain $navChain,
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
                ],
                Response::HTTP_FORBIDDEN,
            );
        }

        $sent = $request->body('msg', '');
        $replyTo = $request->bodyInt('reply_to');
        if (! $request->hasBody('submit')) {
            return $this->form($topic, $page, $sent, $replyTo, []);
        }

        $msg = trim($this->editorContentNormalizer->trimEdgeEmptyBlocks($sent));
        $files = $this->uploadedFiles->fromField($request->files->get('files'));
        $errors = $this->checks->errors($msg);
        if ($errors === [] && ($fileErrors = $this->postFiles->check($files)) !== []) {
            $errors = ['files' => $fileErrors];
        }
        if ($errors === [] && ! $this->formTokens->consume($request->body('token'))) {
            $errors = ['_form' => [__('The form was already sent or is out of date. Send it again if needed.')]];
        }
        if ($errors !== []) {
            return $this->form($topic, $page, $sent, $replyTo, $errors);
        }

        if ($this->session->get('fsort_id') === $topic->id) {
            $this->session->remove(['fsort_id', 'fsort_users']);
        }

        $answered = $this->answeredPosts->find($topic, $replyTo);

        try {
            $result = $this->transactions->run(function () use ($topic, $msg, $files, $answered) {
                $result = $answered === null
                    ? $this->postMessageUseCase->execute(
                        topic: $topic,
                        messageText: $msg,
                        withFiles: $files !== [],
                        clientInfo: $this->environment->getClientInfo(),
                    )
                    : $this->replyMessageUseCase->execute(
                        sourceMessage: $answered,
                        topic: $topic,
                        messageText: $msg,
                        clientInfo: $this->environment->getClientInfo(),
                    );
                $this->pictures->bind($result->messageId, $this->currentUser->id());
                // Last: a failure after the files are written would leave them behind.
                $this->postFiles->store($result->messageId, $files);

                return $result;
            });
        } catch (EditorUploadBindException $exception) {
            return $this->form($topic, $page, $sent, $replyTo, ['message' => [$exception->getMessage()]]);
        } catch (StorageException) {
            return $this->form($topic, $page, $sent, $replyTo, ['files' => [__('The files could not be saved. Try again.')]]);
        }

        redirect($this->topicPathService->getTopicUrlById($result->topicId, $result->page > 1 ? $result->page : null) ?? '/forum/');
    }

    /**
     * The page of the form. Sent back, it keeps the text and the post it answers and says what was
     * wrong beside the fields; the files are chosen anew.
     *
     * @param array<string, list<string>> $errors
     */
    private function form(ForumTopic $topic, int $page, string $msg, int $replyTo, array $errors): ViewResponse
    {
        $backUrl = $topic->url . ($page > 1 ? '?page=' . $page : '');
        $this->navChain->add(__('Forum'), '/forum/');
        ForumUtils::buildBreadcrumbs($topic->section_id, $topic->name, $backUrl);
        $this->navChain->add(__('New message'));

        return new ViewResponse(
            '@forum/public/reply-message.twig',
            [
                'title'       => __('New message'),
                'page_title'  => __('New message'),
                'token'       => $this->formTokens->issue(),
                'topic_name'  => $topic->name,
                'form_action' => '/forum/new-message/' . $topic->id . '/' . ($page > 1 ? '?page=' . $page : ''),
                'msg'         => $msg,
                'reply_to'    => $replyTo,
                'back_url'    => $backUrl,
                'file_limits' => $this->postFiles->limits(),
                'errors'      => $errors,
            ],
            $errors !== [] ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK,
        );
    }
}
