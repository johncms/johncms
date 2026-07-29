<?php

declare(strict_types=1);

namespace Johncms\Modules\Guestbook\Application\Controllers;

use Exception;
use Johncms\FileInfo;
use Johncms\Files\FileStorage;
use Johncms\Http\Controller\ControllerContext;
use Johncms\Http\UploadedFileMapper;
use Johncms\Http\Request;
use League\Flysystem\FilesystemException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;

final readonly class UploadFileController
{
    public function __construct(
        private ControllerContext $context,
        private FileStorage $fileStorage,
        private UploadedFileMapper $uploadedFileMapper,
    ) {
        $this->context->initModule('guestbook');
    }

    public function __invoke(Request $request): JsonResponse
    {
        try {
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

            $file_info = new FileInfo((string) $this->uploadedFileMapper->fromUploadedFile($upload)->clientName);
            if (! $file_info->isImage()) {
                return new JsonResponse(
                    [
                        'error' => [
                            'message' => __('Only images are allowed'),
                        ],
                    ]
                );
            }

            $file = $this->fileStorage->saveFromRequest('upload', 'guestbook');
            $file_array = [
                'id'       => $file->id,
                'name'     => $file->name,
                'uploaded' => 1,
                'url'      => $file->url,
            ];
            return new JsonResponse($file_array);
        } catch (FilesystemException | Exception $e) {
            return new JsonResponse(
                [
                    'error' => [
                        'message' => $e->getMessage(),
                    ],
                ],
                JsonResponse::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
