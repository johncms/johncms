<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\CurrentUser;
use Johncms\Comments\Application\CommentExcerpt;
use Johncms\Database\TransactionManagerInterface;
use Johncms\EditorUploads\EditorUploadBindException;
use Johncms\Http\Environment;
use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\Http\UploadedFileMapper;
use Johncms\Http\View\ViewResponse;
use Johncms\Modules\Forum\Application\DTO\ReplyMessageContextDTO;
use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Exceptions\ForumNotFoundException;
use Johncms\Modules\Forum\Application\ForumUtils;
use Johncms\Modules\Forum\Application\Services\ForumErrorRenderer;
use Johncms\Modules\Forum\Application\Services\ForumFormTokens;
use Johncms\Modules\Forum\Application\Services\ForumPermissions;
use Johncms\Modules\Forum\Application\Services\ForumPostChecks;
use Johncms\Modules\Forum\Application\Services\ForumTopicPathService;
use Johncms\Modules\Forum\Application\Uploads\ForumMessagePictures;
use Johncms\Modules\Forum\Application\Uploads\ForumPostFiles;
use Johncms\Modules\Forum\Application\UseCases\GetReplyMessageContextUseCase;
use Johncms\Modules\Forum\Application\UseCases\ReplyMessageUseCase;
use Johncms\NavChain;
use Johncms\Storage\StorageException;
use Johncms\System\Utility\EditorContentNormalizer;
use Symfony\Component\HttpFoundation\Response;

/**
 * An answer to a post on a page of its own: the way the Reply and Quote buttons take without the
 * script, and the answer sent back with what was wrong.
 */
final readonly class ReplyMessageController
{
    public function __construct(
        private Environment $environment,
        private Session $session,
        private EditorContentNormalizer $editorContentNormalizer,
        private CurrentUser $currentUser,
        private ForumErrorRenderer $forumErrorRenderer,
        private GetReplyMessageContextUseCase $contextUseCase,
        private ReplyMessageUseCase $replyMessageUseCase,
        private ForumMessagePictures $pictures,
        private TransactionManagerInterface $transactions,
        private ForumTopicPathService $topicPathService,
        private AccessCheckerInterface $accessChecker,
        private ForumFormTokens $formTokens,
        private ForumPostFiles $postFiles,
        private ForumPostChecks $checks,
        private UploadedFileMapper $uploadedFiles,
        private CommentExcerpt $excerpt,
        private NavChain $navChain,
    ) {
    }

    public function __invoke(Request $request, int $id): ViewResponse
    {
        $page = max(1, $request->queryInt('page', 1));

        try {
            $context = $this->contextUseCase->execute($id);
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

        $topic = $context->topic;
        $sourceMessage = $context->message;

        $refusal = null;
        if (
            ($topic->deleted && ! $this->accessChecker->allows(ForumPermissions::DELETED_VIEW))
            || ($topic->closed && ! $this->accessChecker->allows(ForumPermissions::TOPIC_MODERATE))
        ) {
            $refusal = __('You cannot write in a closed topic');
        } elseif ($sourceMessage->user_id === $this->currentUser->id()) {
            $refusal = __('You can not reply to your own message');
        }
        if ($refusal !== null) {
            return new ViewResponse(
                '@theme/pages/result.twig',
                [
                    'title'         => __('New message'),
                    'type'          => 'alert-danger',
                    'message'       => $refusal,
                    'back_url'      => $topic->url,
                    'back_url_name' => __('Back'),
                ],
                Response::HTTP_FORBIDDEN,
            );
        }

        if (! $request->hasBody('submit')) {
            return $this->form($request, $context, $page, $this->opening($request, $context), []);
        }

        $sent = $request->body('msg');
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
            return $this->form($request, $context, $page, $sent, $errors);
        }

        if ($this->session->get('fsort_id') === $topic->id) {
            $this->session->remove(['fsort_id', 'fsort_users']);
        }

        try {
            $result = $this->transactions->run(function () use ($sourceMessage, $topic, $msg, $files) {
                $result = $this->replyMessageUseCase->execute(
                    sourceMessage: $sourceMessage,
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
            return $this->form($request, $context, $page, $sent, ['message' => [$exception->getMessage()]]);
        } catch (StorageException) {
            return $this->form($request, $context, $page, $sent, ['files' => [__('The files could not be saved. Try again.')]]);
        }

        redirect($this->topicPathService->getTopicUrlById($result->topicId, $result->page > 1 ? $result->page : null) ?? '/forum/');
    }

    /** What the editor opens with: the post quoted, or the name of its author to address. */
    private function opening(Request $request, ReplyMessageContextDTO $context): string
    {
        if ($request->query->has('quote')) {
            return '<blockquote>' . $context->message->getRawOriginal('text') . '</blockquote><p></p>';
        }

        return '<p>' . htmlspecialchars($context->message->user_name) . ',&nbsp;</p>';
    }

    /**
     * The page of the answer. Sent back, it keeps the text and says what was wrong beside the
     * fields; the files are chosen anew.
     *
     * @param array<string, list<string>> $errors
     */
    private function form(Request $request, ReplyMessageContextDTO $context, int $page, string $msg, array $errors): ViewResponse
    {
        $topic = $context->topic;
        $backUrl = $topic->url . ($page > 1 ? '?page=' . $page : '');
        $this->navChain->add(__('Forum'), '/forum/');
        ForumUtils::buildBreadcrumbs($topic->section_id, $topic->name, $backUrl);
        $this->navChain->add(__('Reply to message'));

        $action = '/forum/reply-message/' . $context->message->id . '/';
        $query = array_filter(['page' => $page > 1 ? $page : null, 'quote' => $request->query->has('quote') ? 1 : null]);

        return new ViewResponse(
            '@forum/public/reply-message.twig',
            [
                'title'       => __('Reply to message'),
                'page_title'  => __('Reply to message'),
                'token'       => $this->formTokens->issue(),
                'topic_name'  => $topic->name,
                'form_action' => $action . ($query !== [] ? '?' . http_build_query($query) : ''),
                'msg'         => $msg,
                'reply_to'    => 0,
                'answered'    => [
                    'name'    => $context->message->user_name,
                    'excerpt' => $this->excerpt->of((string) $context->message->getRawOriginal('text')),
                    'url'     => '/forum/post/' . $context->message->id . '/',
                ],
                'back_url'    => $backUrl,
                'file_limits' => $this->postFiles->limits(),
                'errors'      => $errors,
            ],
            $errors !== [] ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK,
        );
    }
}
