<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application;

use Johncms\System\View\Extension\Assets;
use Johncms\Users\User;

final class FilePresenter
{
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
        private Assets $assets,
        private User $currentUser,
    ) {
    }

    public function present(array $file, bool $withRating = false): array
    {
        $old = $GLOBALS['old'] ?? 0;
        $config = config('johncms');
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $iconId = self::EXTENSIONS[$extension] ?? 9;

        $file['icon'] = $this->assets->url('images/old/system/' . $iconId . '.png');
        $file['detail_url'] = '/downloads/files/' . $file['id'] . '/';
        $file['filtered_name'] = htmlspecialchars($file['rus_name']);
        $file['is_new'] = $file['time'] > $old;

        $file['rating'] = [];
        if ($withRating) {
            $parts = explode('|', $file['rate']);
            $file['rating']['plus'] = $parts[0];
            $file['rating']['minus'] = $parts[1];
        }

        $file['preview_text'] = '';
        if ($file['about']) {
            $about = html_entity_decode(strip_tags((string) $file['about']));
            $file['preview_text'] = htmlentities(mb_strimwidth($about, 0, 94, '...'));
        }

        $file['comments_url'] = '';
        if ($config['mod_down_comm'] || $this->currentUser->rights >= 7) {
            $file['comments_url'] = '/downloads/comments/' . $file['id'];
        }

        return $file;
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
