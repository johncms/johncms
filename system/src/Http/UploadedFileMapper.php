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

use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Maps an HTTP uploaded file into the framework-agnostic UploadedFileDTO.
 *
 * This is the single boundary where the HTTP upload type is read, so Application/Domain code
 * can stay free of it. The Request base class is HttpFoundation, so the source is its
 * UploadedFile; the temporary path is the uploaded file's own pathname.
 */
final readonly class UploadedFileMapper
{
    public function fromUploadedFile(UploadedFile $file): UploadedFileDTO
    {
        $size = $file->getSize();

        return new UploadedFileDTO(
            clientName: $file->getClientOriginalName(),
            mimeType: $file->getClientMimeType(),
            size: $size !== false ? $size : null,
            tmpPath: $file->getPathname(),
            error: $file->getError(),
        );
    }

    /**
     * The files of a field that may hold several (name="files[]" multiple). A field left empty
     * sends one entry without a file: that one is not a file, and is left out.
     *
     * @return list<UploadedFileDTO>
     */
    public function fromField(mixed $field): array
    {
        $files = [];
        foreach (is_array($field) ? $field : [$field] as $file) {
            if ($file instanceof UploadedFile && $file->getError() !== UPLOAD_ERR_NO_FILE) {
                $files[] = $this->fromUploadedFile($file);
            }
        }

        return $files;
    }
}
