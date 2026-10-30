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
}
