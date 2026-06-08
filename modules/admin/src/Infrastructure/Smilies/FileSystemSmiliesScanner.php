<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Infrastructure\Smilies;

use Johncms\Modules\Admin\Domain\Services\SmiliesScannerInterface;
use Johncms\System\Legacy\Tools;

final class FileSystemSmiliesScanner implements SmiliesScannerInterface
{
    private const ALLOWED_EXTENSIONS = ['gif', 'jpg', 'jpeg', 'png'];

    public function __construct(
        private readonly Tools $tools,
    ) {
    }

    public function scan(): array
    {
        $homeUrl = config('johncms')['homeurl'];
        $base = ASSETS_PATH . 'emoticons' . DS;

        $smilies = ['usr' => [], 'adm' => []];

        // Простые смайлы (без алиаса-транслитерации).
        foreach ($this->images($base . 'simply' . DS . '*') as $path) {
            $name = pathinfo($path, PATHINFO_FILENAME);
            $url = $homeUrl . '/assets/emoticons/simply/' . basename($path);
            $smilies['usr'][':' . $name] = $this->img($url);
        }

        // Админские смайлы (код и его транслитерация).
        foreach ($this->images($base . 'admin' . DS . '*') as $path) {
            $name = pathinfo($path, PATHINFO_FILENAME);
            $url = $homeUrl . '/assets/emoticons/admin/' . basename($path);
            $smilies['adm'][':' . $this->tools->trans($name) . ':'] = $this->img($url);
            $smilies['adm'][':' . $name . ':'] = $this->img($url);
        }

        // Смайлы пользовательских каталогов.
        foreach ($this->images($base . 'user' . DS . '*' . DS . '*') as $path) {
            $name = pathinfo($path, PATHINFO_FILENAME);
            $url = $homeUrl . '/assets/emoticons/user/' . basename(dirname($path)) . '/' . basename($path);
            $smilies['usr'][':' . $this->tools->trans($name) . ':'] = $this->img($url);
            $smilies['usr'][':' . $name . ':'] = $this->img($url);
        }

        return $smilies;
    }

    /**
     * @return list<string> Полные пути к файлам с разрешённым расширением.
     */
    private function images(string $pattern): array
    {
        $result = [];
        foreach (glob($pattern) ?: [] as $path) {
            $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            if (in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
                $result[] = $path;
            }
        }

        return $result;
    }

    private function img(string $url): string
    {
        return '<img src="' . $url . '" alt="" />';
    }
}
