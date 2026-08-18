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
use Johncms\Files\Exceptions\BadRequest;
use Johncms\Files\Exceptions\FileNotFound;
use Johncms\Http\Request;
use Johncms\Storage\StorageException;
use Johncms\Storage\StorageRegistryInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileStorage
{
    /**
     * Saving files from the request
     *
     * The request is a parameter rather than a dependency: this class outlives a single request,
     * so resolving it from the container would hand it a request already served.
     *
     * @param string $field_name
     * @param string $working_dir
     * @param bool $multiple
     * @return Models\File|Models\File[]
     * @throws StorageException
     */
    public function saveFromRequest(Request $request, string $field_name, string $working_dir, bool $multiple = false)
    {
        $request_files = $request->files->all();
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
            $uploaded_file->move(dirname($tmp_file), basename($tmp_file));

            $saved_files[] = (new File($tmp_file))
                ->setFileName($uploaded_file->getClientOriginalName() ?: 'untitled_file')
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
            $filename = UPLOAD_PATH . 'tmp' . DS . uniqid('uploaded_file_');
            if (! file_exists($filename)) {
                break;
            }
        }
        return $filename;
    }

    /**
     * @param int $id
     * @throws StorageException
     * @throws Exception
     */
    public function delete(int $id): void
    {
        /** @var \Johncms\Files\Models\File|null $file */
        $file = (new \Johncms\Files\Models\File())->find($id);
        if ($file === null) {
            throw new FileNotFound(sprintf('File #%s not found', $id));
        }
        di(StorageRegistryInterface::class)->disk($file->storage)->delete($file->path);
        $file->delete();
    }
}
