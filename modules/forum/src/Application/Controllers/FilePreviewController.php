<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\FileInfo;
use Johncms\Http\CachedImageResponse;
use Johncms\Http\Request;
use Johncms\Image\ImageProcessingException;
use Johncms\Image\ThumbnailGenerator;
use Johncms\Modules\Forum\Domain\Repository\ForumFileRepositoryInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * The thumbnail of an image attached to a post.
 *
 * The attachment is named by its id, never by its path: the script this replaced took the
 * file name from the query string and pasted it into a path, so a name with "../" in it read
 * any picture on the disk.
 */
final readonly class FilePreviewController
{
    private const int PREVIEW_SIZE = 100;

    public function __construct(
        private ForumFileRepositoryInterface $fileRepository,
        private ThumbnailGenerator $thumbnails,
    ) {
    }

    public function __invoke(Request $request, int $id): Response
    {
        $file = $this->fileRepository->findById($id);
        if ($file === null) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        $path = UPLOAD_PATH . 'forum' . DS . 'attach' . DS . basename((string) $file->filename);
        $fileInfo = new FileInfo($path);
        if (! $fileInfo->isFile() || ! $fileInfo->isImage()) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        try {
            $preview = $this->thumbnails->scaledDown($path, self::PREVIEW_SIZE, self::PREVIEW_SIZE);
        } catch (ImageProcessingException) {
            // A corrupt attachment is not an error of the page that shows it: the picture is
            // simply not there.
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        $response = new CachedImageResponse($preview);
        // A browser coming back after the max-age expired gets a 304 instead of the picture.
        $response->isNotModified($request);

        return $response;
    }
}
