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

use RuntimeException;

/**
 * Framework-agnostic representation of an uploaded file.
 *
 * Application and Domain code must depend on this DTO, never on an HTTP upload type
 * (PSR-7 UploadedFileInterface, HttpFoundation UploadedFile) — see .agents/architecture.md
 * "HTTP types stay in the HTTP layer". Controllers/middleware build it with UploadedFileMapper.
 *
 * It carries the upload metadata plus the temporary path, and owns the move operation so that
 * consumers do not need the original HTTP object: moveTo() replaces the former
 * UploadedFileInterface::moveTo(), and the temporary path replaces getStream() for readers
 * (e.g. Intervention's ImageManager::make() accepts a path).
 */
final readonly class UploadedFileDTO
{
    public function __construct(
        public ?string $clientName,
        public ?string $mimeType,
        public ?int $size,
        public string $tmpPath,
        public int $error,
    ) {
    }

    public function isValid(): bool
    {
        return $this->error === UPLOAD_ERR_OK;
    }

    /**
     * Move the temporary file to the target path.
     *
     * Uses move_uploaded_file() for genuine HTTP uploads and falls back to rename() for
     * non-upload temporary files (worker runtime, tests) — the same distinction the
     * frameworks make internally.
     *
     * @throws RuntimeException when the file cannot be moved.
     */
    public function moveTo(string $target): void
    {
        // The return value is checked below; suppress the native warning on failure so the
        // caller gets a clean exception instead.
        $moved = is_uploaded_file($this->tmpPath)
            ? @move_uploaded_file($this->tmpPath, $target)
            : @rename($this->tmpPath, $target);

        if (! $moved) {
            throw new RuntimeException(sprintf('Could not move the uploaded file to "%s".', $target));
        }
    }
}
