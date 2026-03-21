<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use GuzzleHttp\Psr7\UploadedFile;
use Johncms\FileInfo;
use Johncms\Files\FileStorage;
use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\UseCases\EnsureForumAccessUseCase;
use Johncms\System\Http\Request;
use Johncms\Users\User;
use Psr\Log\LoggerInterface;
use Throwable;

final readonly class UploadFileController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Request $request,
        private FileStorage $fileStorage,
        private EnsureForumAccessUseCase $forumAccessUseCase,
        private User $currentUser,
        private LoggerInterface $logger,
    ) {
        $this->controllerContext->initModule('forum');
    }

    public function __invoke(): string
    {
        header('Content-Type: application/json');

        try {
            $this->forumAccessUseCase->execute();
        } catch (ForumAccessDeniedException) {
            http_response_code(403);
            return json_encode(['error' => ['message' => __('Access denied')]]);
        }

        $config = config('johncms');
        if (
            ! $this->currentUser->isValid()
            || isset($this->currentUser->ban[1])
            || isset($this->currentUser->ban[11])
            || (! $this->currentUser->rights && $config['mod_forum'] === 3)
        ) {
            http_response_code(403);
            return json_encode(['error' => ['message' => __('Access denied')]]);
        }

        try {
            /** @var UploadedFile[] $files */
            $files = $this->request->getUploadedFiles();
            if (empty($files['upload']) || ! $files['upload'] instanceof UploadedFile) {
                return json_encode(['error' => ['message' => __('Error uploading file')]]);
            }

            $fileInfo = new FileInfo((string) $files['upload']->getClientFilename());
            if (! $fileInfo->isImage()) {
                return json_encode(['error' => ['message' => __('Only images are allowed')]]);
            }

            $file = $this->fileStorage->saveFromRequest('upload', 'forum_files');

            return json_encode(
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
                    'request_data' => $this->request->getParsedBody(),
                ]
            );

            http_response_code(500);
            return json_encode(['error' => ['message' => __('Error uploading file')]]);
        }
    }
}
