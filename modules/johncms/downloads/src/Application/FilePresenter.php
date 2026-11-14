<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Modules\Downloads\Application\Services\DownloadsPermissions;
use Johncms\Modules\Downloads\Application\Services\DownloadFilePathService;
use Johncms\Modules\Downloads\Domain\Models\DownloadFile;
use Johncms\Utils\DateFormatterInterface;

final class FilePresenter
{
    /** How long a file keeps the "new" mark, in seconds. */
    public const NEW_FILE_PERIOD = 259200;

    /** The icon of a file by its extension; anything else is a plain file. */
    private const array TYPE_ICONS = [
        'zip'  => 'file-archive',
        'rar'  => 'file-archive',
        '7z'   => 'file-archive',
        'tar'  => 'file-archive',
        'gz'   => 'file-archive',
        'jar'  => 'file-archive',
        'apk'  => 'file-archive',
        'png'  => 'file-image',
        'jpg'  => 'file-image',
        'jpeg' => 'file-image',
        'gif'  => 'file-image',
        'bmp'  => 'file-image',
        'webp' => 'file-image',
        'mp3'  => 'file-music',
        'aac'  => 'file-music',
        'm4a'  => 'file-music',
        'ogg'  => 'file-music',
        'wav'  => 'file-music',
        'mp4'  => 'file-video-camera',
        '3gp'  => 'file-video-camera',
        'webm' => 'file-video-camera',
        'avi'  => 'file-video-camera',
        'mov'  => 'file-video-camera',
        'mpg'  => 'file-video-camera',
        'txt'  => 'file-text',
        'pdf'  => 'file-text',
        'fb2'  => 'file-text',
        'djvu' => 'file-text',
    ];

    public function __construct(
        private AccessCheckerInterface $accessChecker,
        private DownloadFilePathService $filePathService,
        private DateFormatterInterface $dateFormatter,
    ) {
    }

    public function present(DownloadFile $file, bool $withRating = false): array
    {
        $old = time() - self::NEW_FILE_PERIOD;
        $config = config('johncms');
        $extension = strtolower(pathinfo($file->name, PATHINFO_EXTENSION));
        $path = $file->dir . '/' . $file->name;

        $data = $file->toArray();
        $data['type_icon'] = self::TYPE_ICONS[$extension] ?? 'file';
        $data['display_size'] = is_file($path) ? self::formatFileSize((int) filesize($path)) : '';
        $data['display_date'] = $this->dateFormatter->day((int) $file->time);
        $data['detail_url'] = $this->filePathService->getFileUrl($file);
        $data['is_new'] = $file->time > $old;

        $data['rating'] = [];
        if ($withRating) {
            $parts = explode('|', $file->rate);
            $data['rating']['plus'] = $parts[0];
            $data['rating']['minus'] = $parts[1];
        }

        $data['preview_text'] = '';
        if ($file->about) {
            $about = html_entity_decode(strip_tags((string) $file->about));
            $data['preview_text'] = mb_strimwidth($about, 0, 94, '...');
        }

        $data['comments_url'] = '';
        if ($config['mod_down_comm'] || $this->accessChecker->allows(DownloadsPermissions::COMMENTS_ALWAYS_VIEW)) {
            $data['comments_url'] = '/downloads/comments/' . $file->id;
        }

        return $data;
    }

    public static function formatFileSize(int|float $size): string
    {
        if ($size >= 1073741824) {
            return round($size / 1073741824 * 100) / 100 . ' Gb';
        }
        if ($size >= 1048576) {
            return round($size / 1048576 * 100) / 100 . ' Mb';
        }
        if ($size >= 1024) {
            return round($size / 1024 * 100) / 100 . ' Kb';
        }
        return $size . ' b';
    }
}
