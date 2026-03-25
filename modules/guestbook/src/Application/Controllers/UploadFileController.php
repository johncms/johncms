<?php

declare(strict_types=1);

namespace Johncms\Modules\Guestbook\Application\Controllers;

use Exception;
use GuzzleHttp\Psr7\UploadedFile;
use Johncms\FileInfo;
use Johncms\Files\FileStorage;
use Johncms\Http\Controller\ControllerContext;
use Johncms\System\Http\Request;
use League\Flysystem\FilesystemException;

final readonly class UploadFileController
{
    public function __construct(
        private ControllerContext $context,
        private Request $request,
        private FileStorage $fileStorage,
    ) {
        $this->context->initModule('guestbook');
    }

    public function __invoke(): string
    {
        header('Content-Type: application/json');

        try {
            /** @var UploadedFile[] $files */
            $files = $this->request->getUploadedFiles();
            if (! isset($files['upload'])) {
                return json_encode(
                    [
                        'error' => [
                            'message' => __('Wrong data'),
                        ],
                    ]
                );
            }

            $file_info = new FileInfo($files['upload']->getClientFilename());
            if (! $file_info->isImage()) {
                return json_encode(
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
            return json_encode($file_array);
        } catch (FilesystemException | Exception $e) {
            http_response_code(500);
            return json_encode(
                [
                    'error' => [
                        'message' => $e->getMessage(),
                    ],
                ]
            );
        }
    }
}
