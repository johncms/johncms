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
     * @return list<string>
     */
    private function files(string $code): array
    {
        $files = glob(ROOT_PATH . 'modules/*/locale/' . $code . '.lng.php') ?: [];
        $files[] = ROOT_PATH . 'system/locale/' . $code . '.ini';
        $files[] = ROOT_PATH . 'system/locale/' . $code . '.lng.php';
        $files[] = ROOT_PATH . 'themes/default/assets/images/flags/' . $code . '.png';
        $files[] = ROOT_PATH . 'themes/default/assets/images/flags/' . $code . '.svg';

        return $files;
    }
}
