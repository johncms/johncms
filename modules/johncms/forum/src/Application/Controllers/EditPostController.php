<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Database\TransactionManagerInterface;
use Johncms\EditorUploads\EditorUploadBindException;
use Johncms\Modules\Forum\Application\Uploads\ForumMessagePictures;
use Johncms\Modules\Forum\Application\Uploads\ForumPostFiles;
use Johncms\Storage\StorageException;
use Johncms\Auth\CurrentUser;
use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Exceptions\ForumNotFoundException;
use Johncms\Modules\Forum\Application\Services\ForumErrorRenderer;
use Johncms\Modules\Forum\Application\Services\ForumFormTokens;
use Johncms\Modules\Forum\Application\UseCases\EditPostUseCase;
use Johncms\Modules\Forum\Application\UseCases\EnsureEditPostAccessUseCase;
use Johncms\Modules\Forum\Application\UseCases\GetEditPostContextUseCase;
use Johncms\Http\Request;
use Johncms\Http\UploadedFileMapper;
use Johncms\Http\View\ViewResponse;
use Symfony\Component\HttpFoundation\Response;
use Johncms\System\Utility\EditorContentNormalizer;

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

        if ($request->hasBody('submit')) {
            $msg = $this->editorContentNormalizer->trimEdgeEmptyBlocks($request->body('msg', ''));
            $msg = trim($msg);
            if ($msg === '') {
                return new ViewResponse(
                    '@theme/pages/result.twig',
                    [
                        'title'         => __('Edit Message'),
                        'type'          => 'alert-danger',
                        'message'       => __('You have not entered the message'),
                        'back_url'      => '/forum/edit-post/' . $id . '/' . ($page > 1 ? '?page=' . $page : ''),
                        'back_url_name' => __('Repeat'),
                    ]
                );
            }

            $files = $this->uploadedFiles->fromField($request->files->get('files'));
            $fileErrors = $this->postFiles->check($files, $this->postFiles->countOf($context->message->id));
            if ($fileErrors !== []) {
                return $this->form(
                    id: $id,
                    page: $page,
                    message: $request->body('msg'),
                    backUrl: $context->backUrl,
                    errors: ['files' => $fileErrors],
                );
            }

            if (! $this->formTokens->consume($request->body('token', ''))) {
                return $this->form(
                    id: $id,
                    page: $page,
                    message: $request->body('msg'),
                    backUrl: $context->backUrl,
                    errors: ['_form' => [__('The form was already sent or is out of date. Send it again if needed.')]],
                );
            }

            try {
                $this->transactions->run(function () use ($context, $msg, $files): void {
                    $this->editPostUseCase->execute($context, $msg);
                    $this->pictures->bind($context->message->id, $this->currentUser->id());
                    // Last: a failure after the files are written would leave them behind.
                    $this->postFiles->store($context->message->id, $files);
                });
            } catch (StorageException) {
                return $this->form(
                    id: $id,
                    page: $page,
                    message: $request->body('msg'),
                    backUrl: $context->backUrl,
                    errors: ['files' => [__('The files could not be saved. Try again.')]],
                );
            } catch (EditorUploadBindException $exception) {
                return new ViewResponse(
                    '@theme/pages/result.twig',
                    [
                        'title'         => __('Edit Message'),
                        'type'          => 'alert-danger',
                        'message'       => $exception->getMessage(),
                        'back_url'      => '/forum/edit-post/' . $id . '/' . ($page > 1 ? '?page=' . $page : ''),
                        'back_url_name' => __('Back'),
                    ]
                );
            }

            redirect($context->topic->url . ($context->page > 1 ? '?page=' . $context->page : ''));
        }

        $message = ! $request->hasBody('msg')
            ? (string) $context->message->getRawOriginal('text')
            : $request->body('msg');

        return $this->form(id: $id, page: $page, message: $message, backUrl: $context->backUrl, errors: []);
    }

    /**
     * @param array<string, list<string>> $errors
     */
    private function form(int $id, int $page, string $message, string $backUrl, array $errors): ViewResponse
    {
        // The files are chosen anew; the field counts those the post holds already.
        return new ViewResponse(
            '@forum/public/edit-post.twig',
            [
                'title'          => __('Edit Message'),
                'page_title'     => __('Edit Message'),
                'action_url'     => '/forum/edit-post/' . $id . '/' . ($page > 1 ? '?page=' . $page : ''),
                'msg'            => $message,
                'back_url'       => $backUrl,
                'errors'         => $errors,
                'token'          => $this->formTokens->issue(),
                'file_limits'    => $this->postFiles->limits(),
                'files_attached' => $this->postFiles->countOf($id),
            ],
            $errors ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK,
        );
    }
}
