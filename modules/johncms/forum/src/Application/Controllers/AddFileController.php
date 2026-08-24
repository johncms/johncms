<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Http\UploadedFileMapper;
use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Exceptions\UploadException;
use Johncms\Modules\Forum\Application\Exceptions\UploadExpiredException;
use Johncms\Modules\Forum\Application\Services\ForumErrorRenderer;
use Johncms\Modules\Forum\Application\Services\ForumTopicPathService;
use Johncms\Modules\Forum\Application\UseCases\AttachFileToPostUseCase;
use Johncms\Modules\Forum\Application\UseCases\GetAttachFileContextUseCase;
use Johncms\Modules\Forum\Application\Exceptions\ForumNotFoundException;
use Johncms\Http\Request;
use Johncms\Http\View\ViewResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final readonly class AddFileController
{
    public function __construct(
        private ForumErrorRenderer $forumErrorRenderer,
        private GetAttachFileContextUseCase $contextUseCase,
        private AttachFileToPostUseCase $attachFileToPostUseCase,
        private ForumTopicPathService $topicPathService,
        private UploadedFileMapper $uploadedFileMapper,
    ) {
    }

    public function __invoke(Request $request, int $id): ViewResponse
    {
        $config = config('johncms');
        $forumConfig = config('forum');
        $page = $request->queryInt('page', 1);

        try {
            $context = $this->contextUseCase->execute($id, $page);
        } catch (ForumAccessDeniedException | ForumNotFoundException $exception) {
            return $this->forumErrorRenderer->viewResponse(
                $exception,
                [
                    'back_url'      => '/forum/',
                    'back_url_name' => __('Back'),
                ]
            );
        } catch (UploadExpiredException $exception) {
            return $this->forumErrorRenderer->viewResponse(
                $exception,
                [
                    'title'         => __('Add file'),
                    'message'       => __('The time allotted for the file upload has expired'),
                    'back_url'      => $this->getTopicUrl($exception->getTopicId(), $exception->getPage()),
                    'back_url_name' => __('Back'),
                ]
            );
        }

        $fileAttached = false;
        $topicId = $context->topicId;
        $page = $context->page;

        if ($request->getMethod() === 'POST') {
            $uploaded = $request->files->get('fail');
            $file = $uploaded instanceof UploadedFile
                ? $this->uploadedFileMapper->fromUploadedFile($uploaded)
                : null;

            try {
                $result = $this->attachFileToPostUseCase->execute(
                    messageId: $id,
                    extensions: $forumConfig['extensions'],
                    maxFileSizeKb: (int) $config['flsz'],
                    file: $file,
                );
            } catch (ForumNotFoundException $exception) {
                return $this->forumErrorRenderer->viewResponse(
                    $exception,
                    [
                        'back_url'      => '/forum/',
                        'back_url_name' => __('Back'),
                    ]
                );
            } catch (UploadException $exception) {
                return $this->forumErrorRenderer->viewResponse(
                    $exception,
                    [
                        'title'         => __('Add file'),
                        'page_title'    => __('Error uploading file'),
                        'message'       => $exception->getErrors() ?: __('Error uploading file'),
                        'back_url'      => '/forum/addfile/' . $id . '/',
                        'back_url_name' => __('Repeat'),
                    ]
                );
            }

            $fileAttached = $result->fileAttached;
            $page = $result->page;
            $topicId = $result->topicId;
        }

        return new ViewResponse(
            '@forum/public/add-file.twig',
            [
                'title'         => __('Add File'),
                'page_title'    => __('Add File'),
                'action_url'    => '/forum/addfile/' . $id . '/',
                'file_attached' => $fileAttached,
                'back_url'      => $this->getTopicUrl($topicId, $page),
                'max_size'      => (int) $config['flsz'],
            ]
        );
    }

    private function getTopicUrl(int $topicId, int $page): string
    {
        return $this->topicPathService->getTopicUrlById($topicId, $page > 1 ? $page : null) ?? '/forum/';
    }
}
