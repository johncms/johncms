<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Http;

use Johncms\EditorUploads\EditorUploads;
use Johncms\Files\StoredFileDTO;
use Johncms\Image\EditorImageUploadException;
use Johncms\Image\StoreEditorImageUseCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Throwable;

/**
 * The HTTP half of an upload from the text editor: reading the file out of the request and
 * answering in the shape the editor expects.
 *
 * A controller is left with what is its own — who may upload here, and into which directory.
 * The four editors of the site used to carry a copy of this each, and they had drifted: one of
 * them checked the ban of the visitor, another swallowed the reason an upload failed.
 *
 * The failure of an upload is answered with 200 and an `error.message` on purpose: that is the
 * field the editor puts in front of the visitor, and a status of its own would only lose it.
 */
final readonly class EditorImageUploadResponder
{
    /** Name of the form field the editor uploads under. */
    private const string FIELD = 'upload';

    public function __construct(
        private StoreEditorImageUseCase $storeEditorImage,
        private EditorUploads $editorUploads,
        private UploadedFileMapper $uploadedFileMapper,
        private UploadLimits $uploadLimits,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param string $directory Directory of the disk to store under, e.g. `forum_files`.
     * @param string $module Name of the module, for the log of a failure.
     */
    public function store(Request $request, string $directory, string $module): JsonResponse
    {
        return $this->respond(
            $request,
            fn(UploadedFileDTO $upload): StoredFileDTO => $this->storeEditorImage->execute($upload, $directory),
            ['module' => $module, 'directory' => $directory]
        );
    }

    /**
     * The same for a record whose pictures EditorUploads tracks: the upload is registered as the
     * user's, counted against their limit, and taken by the record when its form is sent.
     *
     * @param string $ownerType The type an EditorUploadConsumerInterface declared, e.g. `comment`.
     */
    public function storeTracked(Request $request, string $ownerType, int $userId): JsonResponse
    {
        return $this->respond(
            $request,
            fn(UploadedFileDTO $upload): StoredFileDTO => $this->editorUploads->store($upload, $ownerType, $userId),
            ['owner_type' => $ownerType]
        );
    }

    /**
     * @param callable(UploadedFileDTO): StoredFileDTO $store
     * @param array<string, string> $context What the log of a failure says about the upload.
     */
    private function respond(Request $request, callable $store, array $context): JsonResponse
    {
        $upload = $request->files->get(self::FIELD);
        if (! $upload instanceof UploadedFile) {
            // A body over post_max_size is discarded before the script runs, so the file the
            // visitor picked is simply not there. Saying "wrong data" to that is what used to
            // leave them retrying the same photo.
            return $this->error(
                $this->uploadLimits->postMaxSizeExceeded($request)
                    ? d__('system', 'The file is too large for the server to accept')
                    : d__('system', 'Wrong data')
            );
        }

        try {
            $file = $store($this->uploadedFileMapper->fromUploadedFile($upload));
        } catch (EditorImageUploadException $exception) {
            return $this->error($exception->getMessage());
        } catch (Throwable $exception) {
            $this->logger->error(
                'Could not store a picture uploaded from the editor.',
                ['exception' => $exception] + $context
            );

            return $this->error(d__('system', 'Error uploading file'), JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
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

    public function accessDenied(): JsonResponse
    {
        return $this->error(d__('system', 'Access denied'), JsonResponse::HTTP_FORBIDDEN);
    }

    private function error(string $message, int $status = JsonResponse::HTTP_OK): JsonResponse
    {
        return new JsonResponse(['error' => ['message' => $message]], $status);
    }
}
