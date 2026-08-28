<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Image;

/**
 * What the site does with a picture uploaded into the text editor.
 *
 * In the core rather than in a module: the forum, the guestbook and the comments of the news all
 * upload into the same editor, and the admin panel edits the values. All of them have to read
 * them the same way.
 *
 * The values are read on every call instead of in a constructor: the container is compiled and
 * kept, so a setting captured at build time would keep answering with what it was compiled with.
 */
final readonly class EditorImageSettings
{
    public const int DEFAULT_MAX_SIZE_KB = 5120;
    public const int DEFAULT_MAX_WIDTH = 1600;
    public const int DEFAULT_MAX_HEIGHT = 1600;
    public const int DEFAULT_QUALITY = 82;

    /** The largest upload the editor accepts, in bytes. */
    public function maxSizeBytes(): int
    {
        return $this->maxSizeKb() * 1024;
    }

    public function maxSizeKb(): int
    {
        return max(1, $this->value('max_size', self::DEFAULT_MAX_SIZE_KB));
    }

    /**
     * The bounds a stored picture is scaled down to fit in. Null means the side is not
     * constrained — a site that wants the pictures kept as they came sets both to zero.
     */
    public function maxWidth(): ?int
    {
        return $this->bound('max_width', self::DEFAULT_MAX_WIDTH);
    }

    public function maxHeight(): ?int
    {
        return $this->bound('max_height', self::DEFAULT_MAX_HEIGHT);
    }

    /** Encoding quality of the re-encoded picture, in percent. */
    public function quality(): int
    {
        return min(100, max(1, $this->value('quality', self::DEFAULT_QUALITY)));
    }

    public function format(): EditorImageFormat
    {
        return EditorImageFormat::fromValue($this->settings()['convert'] ?? null);
    }

    private function bound(string $key, int $default): ?int
    {
        $value = $this->value($key, $default);

        return $value > 0 ? $value : null;
    }

    private function value(string $key, int $default): int
    {
        $value = filter_var($this->settings()[$key] ?? null, FILTER_VALIDATE_INT);

        return $value === false ? $default : $value;
    }

    /**
     * @return array<string, mixed>
     */
    private function settings(): array
    {
        $settings = config('johncms.editor_images');

        return is_array($settings) ? $settings : [];
    }
}
