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

use GuzzleHttp\Psr7\UploadedFile;
use Johncms\Files\Exceptions\BadRequest;
use Johncms\System\Http\Request;
use League\Flysystem\FilesystemException;

class FileStorage
{
    /**
     * Saving files from the request
     *
     * @param string $field_name
     * @param string $working_dir
     * @param bool $multiple
     * @return Models\File|Models\File[]
     * @throws FilesystemException
     */
    public function saveFromRequest(string $field_name, string $working_dir, bool $multiple = false)
    {
        $request_files = di(Request::class)->getUploadedFiles();
        if (! array_key_exists($field_name, $request_files)) {
            throw new BadRequest(sprintf('There is no file field named "%s" in the request', $field_name));
        }

        if ($multiple) {
            if (! is_array($request_files[$field_name])) {
                throw new BadRequest('Multiple fields are expected');
            }
            $uploaded_files = $request_files[$field_name];
        } else {
            $uploaded_files = [$request_files[$field_name]];
        }

        $saved_files = [];
        /** @var UploadedFile $uploaded_file */
        foreach ($uploaded_files as $uploaded_file) {
            if ($uploaded_file->getError() !== 0) {
                continue;
            }

            $tmp_file = $this->makeTmpName();
            $uploaded_file->moveTo($tmp_file);

            $saved_files[] = (new File($tmp_file))
                ->setFileName($uploaded_file->getClientFilename() ?? 'untitled_file')
                ->setParentDir($working_dir)
                ->save();

            unlink($tmp_file);
        }

        return $multiple ? $saved_files : $saved_files[0];
    }

    /**
     * Generating a temporary file name
     *
     * @return string
     */
    protected function makeTmpName(): string
    {
        while (true) {
            $filename = UPLOAD_PATH . uniqid('uploaded_file_');
            if (! file_exists($filename)) {
                break;
            }
        }
        return $filename;
    }
}
