<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Files;

use Exception;
use GuzzleHttp\Psr7\UploadedFile;
use Johncms\Files\Exceptions\BadRequest;
use Johncms\Files\Exceptions\FileNotFound;
use Johncms\Files\Exceptions\FileUploadException;
use Johncms\Http\Request;
use League\Flysystem\FilesystemException;

class FileStorage
{
    public function __construct(
        protected Filesystem $filesystem
    ) {
    }

    /**
     * Saving files from the request
     *
     * @param string $fieldName
     * @param string $workingDir
     * @param bool $multiple
     * @return Models\File|Models\File[]
     * @throws FilesystemException
     */
    public function saveFromRequest(string $fieldName, string $workingDir, bool $multiple = false): Models\File | array
    {
        $requestFiles = di(Request::class)->getUploadedFiles();
        if (! array_key_exists($fieldName, $requestFiles)) {
            throw new BadRequest(sprintf('There is no file field named "%s" in the request', $fieldName));
        }

        if ($multiple) {
            if (! is_array($requestFiles[$fieldName])) {
                throw new BadRequest('Multiple fields are expected');
            }
            $uploadedFiles = $requestFiles[$fieldName];
        } else {
            $uploadedFiles = [$requestFiles[$fieldName]];
        }

        $saved_files = [];
        /** @var UploadedFile $uploadedFile */
        foreach ($uploadedFiles as $uploadedFile) {
            if ($uploadedFile->getError() !== 0) {
                continue;
            }
            $saved_files[] = $this->saveUploadedFile($uploadedFile, $workingDir);
        }

        return $multiple ? $saved_files : $saved_files[0];
    }

    /**
     * @throws FilesystemException
     */
    public function saveUploadedFile(UploadedFile $uploadedFile, string $workingDir): Models\File
    {
        if ($uploadedFile->getError() !== 0) {
            throw new FileUploadException('File upload error');
        }

        $tmpName = $this->makeTmpName();
        $uploadedFile->moveTo($tmpName);

        $savedFile = (new File($tmpName))
            ->setFileName($uploadedFile->getClientFilename() ?? 'untitled_file')
            ->setParentDir($workingDir)
            ->save();

        unlink($tmpName);
        return $savedFile;
    }

    /**
     * Generating a temporary file name
     *
     * @return string
     */
    protected function makeTmpName(): string
    {
        while (true) {
            $filename = TMP_PATH . uniqid('uploaded_file_');
            if (! file_exists($filename)) {
                break;
            }
        }
        return $filename;
    }

    /**
     * @param int $id
     * @throws FilesystemException
     * @throws Exception
     */
    public function delete(int $id): void
    {
        $file = \Johncms\Files\Models\File::query()->find($id);
        if ($file === null) {
            throw new FileNotFound(sprintf('File #%s not found', $id));
        }
        $this->filesystem->storage($file->storage)->delete($file->path);
        $file->delete();
    }
}
