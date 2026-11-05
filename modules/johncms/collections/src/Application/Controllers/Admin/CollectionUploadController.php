<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Controllers\Admin;

use Johncms\Http\Request;
use Johncms\Http\UploadedFileMapper;
use Johncms\Http\UploadLimits;
use Johncms\Image\EditorImageUploadException;
use Johncms\Files\FileStoreException;
use Johncms\Modules\Collections\Application\Exceptions\CollectionItemFormException;
use Johncms\Modules\Collections\Application\Services\CollectionItemFiles;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;

final readonly class CollectionUploadController
{
    public function __construct(private CollectionItemFiles $files, private UploadedFileMapper $mapper, private UploadLimits $limits, private \Johncms\Modules\Collections\Application\Services\CollectionFieldUpload $fieldUploads)
    {
    }

    public function createContext(Request $request, int $collection_id): JsonResponse
    {
        try {
            return new JsonResponse(['context' => $this->files->createContext($collection_id, $request->bodyInt('item_id') ?: null)]);
        } catch (CollectionItemFormException $exception) {
            return $this->formError($exception);
        }
    }

    public function upload(Request $request, int $collection_id): JsonResponse
    {
        $upload = $request->files->get('upload');
        if (! $upload instanceof UploadedFile) {
            return new JsonResponse(['error' => ['message' => $this->limits->postMaxSizeExceeded($request) ? d__('system', 'The file is too large for the server to accept') : d__('system', 'Wrong data')]]);
        }
        try {
            $file = $request->query->getString('field') !== ''
                ? $this->fieldUploads->upload($collection_id, $request->query->getString('field'), $request->query->getString('context'), $this->mapper->fromUploadedFile($upload))
                : $this->files->upload($collection_id, $request->query->getString('context'), $this->mapper->fromUploadedFile($upload));
            return new JsonResponse(['id' => $file->id, 'code' => (string) $file->id, 'name' => $file->name, 'url' => $file->url, 'uploaded' => 1, 'size' => $file->size, 'size_label' => format_size($file->size),
                'image' => in_array(strtolower(pathinfo($file->name, PATHINFO_EXTENSION)), ['jpg', 'png', 'gif', 'webp'], true)]);
        } catch (CollectionItemFormException $exception) {
            return $this->formError($exception);
        } catch (EditorImageUploadException $exception) {
            return new JsonResponse(['error' => ['message' => $exception->getMessage()]]);
        } catch (FileStoreException) {
            return new JsonResponse(['error' => ['message' => d__('system', 'Error uploading file')]], 500);
        }
    }

    private function formError(CollectionItemFormException $exception): JsonResponse
    {
        return new JsonResponse(['error' => ['message' => implode(' ', array_merge(...array_values($exception->errors)))]], 422);
    }
}
