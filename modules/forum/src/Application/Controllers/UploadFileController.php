<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\FileInfo;
use Johncms\Files\FileStorage;
use Johncms\Http\Request;
use Johncms\Http\UploadedFileMapper;
use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Services\ForumPermissions;
use Johncms\Modules\Forum\Application\UseCases\EnsureForumAccessUseCase;
use Johncms\Users\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Throwable;

final readonly class UploadFileController
{
    public function __construct(
        private AccessCheckerInterface $accessChecker,
        private FileStorage $fileStorage,
        private EnsureForumAccessUseCase $forumAccessUseCase,
        private User $currentUser,
        private LoggerInterface $logger,
        private UploadedFileMapper $uploadedFileMapper,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $this->forumAccessUseCase->execute();
        } catch (ForumAccessDeniedException) {
            return new JsonResponse(['error' => ['message' => __('Access denied')]], JsonResponse::HTTP_FORBIDDEN);
        }

        if (
            ! $this->currentUser->isValid()
            || isset($this->currentUser->ban[1])
            || isset($this->currentUser->ban[11])
            || ! $this->accessChecker->allows(ForumPermissions::POST)
        ) {
            return new JsonResponse(['error' => ['message' => __('Access denied')]], JsonResponse::HTTP_FORBIDDEN);
        }

        try {
            $upload = $request->files->get('upload');
            if (! $upload instanceof UploadedFile) {
                return new JsonResponse(['error' => ['message' => __('Error uploading file')]]);
            }

            $fileInfo = new FileInfo((string) $this->uploadedFileMapper->fromUploadedFile($upload)->clientName);
            if (! $fileInfo->isImage()) {
                return new JsonResponse(['error' => ['message' => __('Only images are allowed')]]);
            }

            $file = $this->fileStorage->saveFromRequest($request, 'upload', 'forum_files');

            return new JsonResponse(
                [
                    'id'       => $file->id,
                    'name'     => $file->name,
                    'uploaded' => 1,
                    'url'      => $file->url,
                ]
            );
        } catch (Throwable $exception) {
            $this->logger->error(
                $exception->getMessage(),
                [
                    'module'       => 'forum',
                    'feature'      => 'upload_file',
                    'user_id'      => $this->currentUser->id,
                    'trace'        => $exception->getTraceAsString(),
                    'request_data' => $request->request->all(),
                ]
            );

            return new JsonResponse(
                ['error' => ['message' => __('Error uploading file')]],
                JsonResponse::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
