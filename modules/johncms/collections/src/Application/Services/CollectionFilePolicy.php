<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Services;

use Johncms\Files\FileStore;
use Johncms\Files\FileStoreException;
use Johncms\Modules\Collections\Application\Exceptions\CollectionItemFormException;

final readonly class CollectionFilePolicy
{
    public const int DEFAULT_MAX_BYTES = 10485760;
    /** The supported formats: the MIME type field settings store and the extension of the format. */
    public const array TYPES = [
        'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp',
        'application/pdf' => 'pdf', 'text/plain' => 'txt', 'text/rtf' => 'rtf',
        'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'application/vnd.oasis.opendocument.text' => 'odt',
        'text/csv' => 'csv',
        'application/vnd.ms-excel' => 'xls',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
        'application/vnd.oasis.opendocument.spreadsheet' => 'ods',
        'application/vnd.ms-powerpoint' => 'ppt',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
        'application/vnd.oasis.opendocument.presentation' => 'odp',
        'application/zip' => 'zip', 'application/vnd.rar' => 'rar', 'application/x-7z-compressed' => '7z',
        'audio/mpeg' => 'mp3', 'audio/ogg' => 'ogg', 'audio/wav' => 'wav', 'audio/mp4' => 'm4a',
        'video/mp4' => 'mp4', 'video/webm' => 'webm', 'video/quicktime' => 'mov',
    ];
    public const array GROUPS = [
        'images'        => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
        'documents'     => [
            'application/pdf', 'text/plain', 'text/rtf', 'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.oasis.opendocument.text',
        ],
        'spreadsheets'  => [
            'text/csv', 'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.oasis.opendocument.spreadsheet',
        ],
        'presentations' => [
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'application/vnd.oasis.opendocument.presentation',
        ],
        'archives'      => ['application/zip', 'application/vnd.rar', 'application/x-7z-compressed'],
        'audio'         => ['audio/mpeg', 'audio/ogg', 'audio/wav', 'audio/mp4'],
        'video'         => ['video/mp4', 'video/webm', 'video/quicktime'],
    ];
    /** What the form of a new field offers checked. */
    public const array DEFAULT_FORMATS = [...self::GROUPS['images'], ...self::GROUPS['documents']];

    /** Other names of a format, as detectors report them. */
    private const array ALIASES = [
        'application/rtf' => 'text/rtf',
        'application/csv' => 'text/csv',
        'text/markdown' => 'text/plain',
        'text/x-log' => 'text/plain',
        'application/x-rar' => 'application/vnd.rar',
        'application/x-rar-compressed' => 'application/vnd.rar',
        'audio/x-wav' => 'audio/wav',
        'audio/wave' => 'audio/wav',
        'audio/vnd.wave' => 'audio/wav',
        'audio/x-m4a' => 'audio/mp4',
        'application/cdfv2' => 'application/x-ole-storage',
    ];
    /** Content that does not tell the format by itself: the extension chooses among these, and only these. */
    private const array CONTAINERS = [
        'application/zip' => [
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'odt' => 'application/vnd.oasis.opendocument.text',
            'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
            'odp' => 'application/vnd.oasis.opendocument.presentation',
        ],
        'application/x-ole-storage' => [
            'doc' => 'application/msword',
            'xls' => 'application/vnd.ms-excel',
            'ppt' => 'application/vnd.ms-powerpoint',
        ],
        'text/plain' => ['csv' => 'text/csv'],
        'text/csv' => ['txt' => 'text/plain', 'md' => 'text/plain', 'log' => 'text/plain'],
    ];
    /** Harmless extensions a format keeps instead of its own. */
    private const array EXTENSIONS = [
        'image/jpeg' => ['jpeg'],
        'text/plain' => ['md', 'log'],
    ];

    public function __construct(private FileStore $files)
    {
    }

    /** The supported format of a file, judged by its content; the name only tells apart what the content cannot. */
    public function format(string $mime, string $name): ?string
    {
        $mime = strtolower($mime);
        $detected = self::ALIASES[$mime] ?? $mime;
        $format = self::CONTAINERS[$detected][$this->extension($name)] ?? $detected;

        return isset(self::TYPES[$format]) ? $format : null;
    }

    /** The name a file of the format is stored under: an extension the format does not know is replaced. */
    public function fileName(string $format, string $name): string
    {
        $extension = $this->extension($name);
        $kept = in_array($extension, self::EXTENSIONS[$format] ?? [], true) ? $extension : self::TYPES[$format];

        return (pathinfo($name, PATHINFO_FILENAME) ?: 'file') . '.' . $kept;
    }

    /**
     * The extensions of the formats, for the file input of the browser.
     *
     * @param list<string> $formats
     * @return list<string>
     */
    public static function extensions(array $formats): array
    {
        $extensions = [];
        foreach ($formats as $format) {
            array_push($extensions, self::TYPES[$format] ?? '', ...self::EXTENSIONS[$format] ?? []);
        }

        return array_values(array_filter($extensions));
    }

    /** @param array<string, mixed> $settings */
    public function assertAllowed(string $code, int $size, string $format, array $settings): void
    {
        if (! isset(self::TYPES[$format]) || $size < 0 || $size > ($settings['max_file_bytes'] ?? self::DEFAULT_MAX_BYTES) || ! in_array($format, $settings['allowed_mime_types'] ?? array_keys(self::TYPES), true)) {
            throw new CollectionItemFormException(['values.' . $code => [d__('collections', 'The file exceeds this field size limit or its content type is not allowed.')]]);
        }
    }

    /** @param array<string, mixed> $settings */
    public function assertStored(int $id, string $code, array $settings): string
    {
        try {
            $stream = $this->files->openStream($id);
        } catch (FileStoreException $exception) {
            throw new CollectionItemFormException(['values.' . $code => [d__('collections', 'The registered file is unavailable. Restore it or upload another file.')]], $exception);
        }
        if ($stream === null) {
            throw new CollectionItemFormException(['values.' . $code => [d__('collections', 'The registered file is unavailable. Restore it or upload another file.')]]);
        }
        try {
            $format = $this->format($stream->mimeType, $stream->name) ?? $stream->mimeType;
            $this->assertAllowed($code, $stream->size, $format, $settings);
            return $format;
        } finally {
            fclose($stream->stream);
        }
    }

    private function extension(string $name): string
    {
        return strtolower(pathinfo($name, PATHINFO_EXTENSION));
    }
}
