<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Auth\CurrentUser;
use Johncms\Database\TransactionManagerInterface;
use Johncms\EditorUploads\EditorUploadBindException;
use Johncms\Http\Request;
use Johncms\Http\UploadedFileMapper;
use Johncms\Http\View\ViewResponse;
use Johncms\Modules\Forum\Application\DTO\EditPostContextDTO;
use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Exceptions\ForumNotFoundException;
use Johncms\Modules\Forum\Application\ForumUtils;
use Johncms\Modules\Forum\Application\Services\ForumErrorRenderer;
use Johncms\Modules\Forum\Application\Services\ForumFormTokens;
use Johncms\Modules\Forum\Application\Uploads\ForumMessagePictures;
use Johncms\Modules\Forum\Application\Uploads\ForumPostFiles;
use Johncms\Modules\Forum\Application\UseCases\EditPostUseCase;
use Johncms\Modules\Forum\Application\UseCases\EnsureEditPostAccessUseCase;
use Johncms\Modules\Forum\Application\UseCases\GetEditPostContextUseCase;
use Johncms\NavChain;
use Johncms\Storage\StorageException;
use Johncms\System\Utility\EditorContentNormalizer;
use Symfony\Component\HttpFoundation\Response;

final readonly class EditPostController
{
    public function __construct(
        private EditorContentNormalizer $editorContentNormalizer,
        private CurrentUser $currentUser,
        private ForumErrorRenderer $forumErrorRenderer,
        private GetEditPostContextUseCase $contextUseCase,
        private EnsureEditPostAccessUseCase $accessUseCase,
        private EditPostUseCase $editPostUseCase,
        private ForumMessagePictures $pictures,
        private TransactionManagerInterface $transactions,
        private ForumFormTokens $formTokens,
        private ForumPostFiles $postFiles,
        private UploadedFileMapper $uploadedFiles,
        private NavChain $navChain,
    ) {
    }

    public function __invoke(Request $request, int $id): ViewResponse
    {
        $page = max(1, $request->queryInt('page', 1));

        try {
            $context = $this->contextUseCase->execute($id);
            $this->accessUseCase->execute($context);
        } catch (ForumNotFoundException $exception) {
            return $this->forumErrorRenderer->viewResponse(
                $exception,
                [
                    'title'         => __('Error'),
                    'message'       => __('Message does not exists or has been deleted'),
                    'back_url'      => '/forum/',
                    'back_url_name' => __('Forum'),
                ]
            );
        } catch (ForumAccessDeniedException $exception) {
            return $this->forumErrorRenderer->viewResponse(
                $exception,
                [
                    'title'         => __('Error'),
                    'message'       => $exception->getMessage(),
                    'back_url'      => $context->backUrl ?? '/forum/',
                    'back_url_name' => __('Back'),
                ]
            );
        }

        if (! $request->hasBody('submit')) {
            return $this->form($context, $page, (string) $context->message->getRawOriginal('text'), []);
        }

        $sent = $request->body('msg');
        $msg = trim($this->editorContentNormalizer->trimEdgeEmptyBlocks($sent));
        if ($msg === '') {
            return $this->form($context, $page, $sent, ['message' => [__('You have not entered the message')]]);
        }

        $files = $this->uploadedFiles->fromField($request->files->get('files'));
        $fileErrors = $this->postFiles->check($files, $this->postFiles->countOf($context->message->id));
        if ($fileErrors !== []) {
            return $this->form($context, $page, $sent, ['files' => $fileErrors]);
        }

        if (! $this->formTokens->consume($request->body('token', ''))) {
            return $this->form($context, $page, $sent, ['_form' => [__('The form was already sent or is out of date. Send it again if needed.')]]);
        }

        try {
            $this->transactions->run(function () use ($context, $msg, $files): void {
                $this->editPostUseCase->execute($context, $msg);
                $this->pictures->bind($context->message->id, $this->currentUser->id());
                // Last: a failure after the files are written would leave them behind.
                $this->postFiles->store($context->message->id, $files);
            });
        } catch (EditorUploadBindException $exception) {
            return $this->form($context, $page, $sent, ['message' => [$exception->getMessage()]]);
        } catch (StorageException) {
            return $this->form($context, $page, $sent, ['files' => [__('The files could not be saved. Try again.')]]);
        }

        redirect($context->topic->url . ($context->page > 1 ? '?page=' . $context->page : ''));
    }

    /**
     * The form, and the form sent back with what was wrong beside the fields: the text stays, the
     * files are chosen anew and counted with those the post holds already.
     *
     * @param array<string, list<string>> $errors
     */
    private function form(EditPostContextDTO $context, int $page, string $message, array $errors): ViewResponse
    {
        $this->navChain->add(__('Forum'), '/forum/');
        ForumUtils::buildBreadcrumbs($context->section->id, $context->topic->name, $context->backUrl);
        $this->navChain->add(__('Edit Message'));

        return new ViewResponse(
            '@forum/public/edit-post.twig',
            [
                'title'          => __('Edit Message'),
                'page_title'     => __('Edit Message'),
                'action_url'     => '/forum/edit-post/' . $context->message->id . '/' . ($page > 1 ? '?page=' . $page : ''),
                'msg'            => $message,
                'back_url'       => $context->backUrl,
                'topic_name'     => $context->topic->name,
                'errors'         => $errors,
                'token'          => $this->formTokens->issue(),
                'file_limits'    => $this->postFiles->limits(),
                'files_attached' => $this->postFiles->countOf($context->message->id),
            ],
            $errors ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK,
        );
    }
}
