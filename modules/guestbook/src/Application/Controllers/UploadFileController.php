<?php

declare(strict_types=1);

namespace Johncms\Modules\Guestbook\Application\Controllers;

use Johncms\FileInfo;
use Johncms\Files\FileStore;
use Johncms\Files\FileStoreException;
use Johncms\Http\Request;
use Johncms\Http\UploadedFileMapper;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;

final readonly class UploadFileController
{
    public function __construct(
        private FileStore $files,
        private UploadedFileMapper $uploadedFileMapper,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $upload = $request->files->get('upload');
        if (! $upload instanceof UploadedFile) {
            return new JsonResponse(
                [
                    'error' => [
                        'message' => __('Wrong data'),
                    ],
                ]
            );
        }

        $uploadedFile = $this->uploadedFileMapper->fromUploadedFile($upload);

        $file_info = new FileInfo((string) $uploadedFile->clientName);
        if (! $file_info->isImage()) {
            return new JsonResponse(
                [
                    'error' => [
                        'message' => __('Only images are allowed'),
                    ],
                ]
            );
        }

        try {
            $file = $this->files->storeUpload($uploadedFile, 'guestbook');
        } catch (FileStoreException $exception) {
            return new JsonResponse(
                [
                    'error' => [
                        'message' => $exception->getMessage(),
                    ],
                ],
                JsonResponse::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        return new JsonResponse(
            [
                'id'       => $file->id,
                'name'     => $file->name,
                'uploaded' => 1,
                'url'      => $file->url,
            ]
        );
    }
}
