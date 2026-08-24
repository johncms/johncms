<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Infrastructure\Language;

use Johncms\Modules\Admin\Domain\Services\LanguageFilesManagerInterface;

final class FileSystemLanguageFilesManager implements LanguageFilesManagerInterface
{
    public function getInstalled(): array
    {
        $list = [];
        foreach (glob(ROOT_PATH . 'system/locale/*.ini') ?: [] as $path) {
            $iso = pathinfo($path, PATHINFO_FILENAME);
            $data = parse_ini_file($path) ?: [];
            $list[$iso] = [
                'name'    => ! empty($data['name']) ? (string) $data['name'] : $iso,
                'version' => ! empty($data['version']) ? (float) $data['version'] : 1.0,
            ];
        }

        return $list;
    }

    public function remove(string $code): void
    {
        foreach ($this->files($code) as $file) {
            @unlink($file);
        }
    }

    public function hasAccessProblem(string $code): bool
    {
        foreach ($this->files($code) as $file) {
            if (is_file($file) && ! is_writable($file)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Every file of a language, found on disk rather than through the module registry on purpose:
     * removing a language has to take the dictionaries of every module whose files are there, the
     * switched-off and the never-installed ones included. Leaving theirs behind would bring the
     * language half back the moment such a module is switched on.
     *
     * @return list<string>
     */
    private function files(string $code): array
    {
        $files = glob(ROOT_PATH . 'modules/*/*/locale/' . $code . '.lng.php') ?: [];
        $files[] = ROOT_PATH . 'system/locale/' . $code . '.ini';
        $files[] = ROOT_PATH . 'system/locale/' . $code . '.lng.php';
        $files[] = PUBLIC_THEMES_PATH . 'default/assets/images/flags/' . $code . '.png';
        $files[] = PUBLIC_THEMES_PATH . 'default/assets/images/flags/' . $code . '.svg';

        return $files;
    }
}
