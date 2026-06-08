<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Infrastructure\Language;

use Johncms\Modules\Admin\Domain\Services\LanguageCatalogInterface;
use ZipArchive;

final class HttpLanguageCatalog implements LanguageCatalogInterface
{
    private const BASE_URL = 'https://johncms.com';

    public function getAvailable(): array
    {
        $url = self::BASE_URL . '/updates/languages/?cms_version=' . CMS_VERSION;
        $response = @file_get_contents($url);
        if (empty($response)) {
            return [];
        }

        $languages = json_decode($response, true);

        return is_array($languages) ? $languages : [];
    }

    public function install(string $code): void
    {
        $languages = $this->getAvailable();
        if (! array_key_exists($code, $languages) || empty($languages[$code]['path'])) {
            return;
        }

        $path = $languages[$code]['path'];
        $tmpFile = DATA_PATH . 'tmp/' . basename($path);

        if (! copy(self::BASE_URL . $path, $tmpFile)) {
            return;
        }

        $zip = new ZipArchive();
        if ($zip->open($tmpFile) === true) {
            $zip->extractTo(ROOT_PATH);
            $zip->close();
        }

        unlink($tmpFile);
    }
}
