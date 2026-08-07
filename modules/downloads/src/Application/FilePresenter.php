<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application;

use Johncms\Modules\Downloads\Application\Services\DownloadFilePathService;
use Johncms\Modules\Downloads\Domain\Models\DownloadFile;
use Johncms\View\Asset\AssetResolver;
use Johncms\Users\User;

final class FilePresenter
{
    /** How long a file keeps the "new" mark, in seconds. */
    public const NEW_FILE_PERIOD = 259200;

    private const EXTENSIONS = [
        'mp3'  => 8,
        'png'  => 5,
        'jpg'  => 5,
        'gif'  => 5,
        'rar'  => 5,
        'zip'  => 6,
        '3gp'  => 7,
        'mp4'  => 7,
        'txt'  => 4,
        'jar'  => 2,
        'sis'  => 1,
        'sisx' => 1,
        'thm'  => 10,
        'nth'  => 11,
    ];

    public function __construct(
        private AssetResolver $assets,
        private User $currentUser,
        private DownloadFilePathService $filePathService,
    ) {
    }

    public function present(DownloadFile $file, bool $withRating = false): array
    {
        $old = time() - self::NEW_FILE_PERIOD;
        $config = config('johncms');
        $extension = pathinfo($file->name, PATHINFO_EXTENSION);
        $iconId = self::EXTENSIONS[$extension] ?? 9;

        $data = $file->toArray();
        $data['icon'] = $this->assets->url('images/old/system/' . $iconId . '.png');
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
        if ($config['mod_down_comm'] || $this->currentUser->rights >= 7) {
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
