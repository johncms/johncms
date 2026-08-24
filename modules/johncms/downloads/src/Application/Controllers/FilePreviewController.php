<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\Controllers;

use Johncms\FileInfo;
use Johncms\Http\CachedImageResponse;
use Johncms\Http\Request;
use Johncms\Image\ImageProcessingException;
use Johncms\Image\ThumbnailGenerator;
use Johncms\Modules\Downloads\Domain\Repository\DownloadFileRepositoryInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Previews of a downloadable picture and of the screenshots attached to a file.
 *
 * Both are addressed by the id of the file they belong to, so nothing the visitor sends ever
 * becomes part of a path — the script this replaced took the path itself from the query
 * string and had to defend against it by hand.
 */
final readonly class FilePreviewController
{
    private const int PREVIEW_WIDTH = 220;
    private const int PREVIEW_HEIGHT = 300;

    public function __construct(
        private DownloadFileRepositoryInterface $fileRepository,
        private ThumbnailGenerator $thumbnails,
    ) {
    }

    /**
     * The downloadable file itself, when it is a picture.
     */
    public function file(Request $request, int $id): Response
    {
        $file = $this->fileRepository->findFile($id);
        if ($file === null) {
            return $this->notFound();
        }

        // The model carries its columns as dynamic attributes, so they arrive untyped.
        $directory = (string) $file->getAttribute('dir');
        $name = (string) $file->getAttribute('name');

        // The directory is stored relative to the document root, the way the rest of the
        // module resolves it; the check below is what keeps the result inside.
        return $this->preview($request, $directory . DS . $name, PUBLIC_PATH);
    }

    /**
     * One of the screenshots of a file.
     */
    public function screen(Request $request, int $id, string $name): Response
    {
        $directory = UPLOAD_PATH . 'downloads' . DS . 'screen' . DS . $id . DS;

        return $this->preview($request, $directory . basename($name), $directory);
    }

    /**
     * @param string $path      The picture to preview.
     * @param string $mustBeIn  Directory the resolved path has to stay inside.
     */
    private function preview(Request $request, string $path, string $mustBeIn): Response
    {
        $realPath = realpath($path);
        $boundary = realpath($mustBeIn);
        if ($realPath === false || $boundary === false || ! str_starts_with($realPath, $boundary . DS)) {
            return $this->notFound();
        }

        $fileInfo = new FileInfo($realPath);
        if (! $fileInfo->isFile() || ! $fileInfo->isImage()) {
            return $this->notFound();
        }

        try {
            $preview = $this->thumbnails->blurredBackdrop($realPath, self::PREVIEW_WIDTH, self::PREVIEW_HEIGHT);
        } catch (ImageProcessingException) {
            return $this->notFound();
        }

        $response = new CachedImageResponse($preview);
        // A browser coming back after the max-age expired gets a 304 instead of the picture.
        $response->isNotModified($request);

        return $response;
    }

    private function notFound(): Response
    {
        return new Response('', Response::HTTP_NOT_FOUND);
    }
}
