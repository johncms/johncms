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
        $tmpDir = DATA_PATH . 'tmp' . DS . 'lang-' . $code . DS;
        $tmpFile = DATA_PATH . 'tmp' . DS . basename($path);

        if (! is_dir($tmpDir) && ! mkdir($tmpDir, 0777, true) && ! is_dir($tmpDir)) {
            return;
        }

        if (! copy(self::BASE_URL . $path, $tmpFile)) {
            $this->removeDirectory($tmpDir);
            return;
        }

        $zip = new ZipArchive();
        if ($zip->open($tmpFile) === true) {
            $zip->extractTo($tmpDir);
            $zip->close();
            $this->publish($tmpDir);
        }

        unlink($tmpFile);
        $this->removeDirectory($tmpDir);
    }

    /**
     * Moves the extracted package into place. Theme assets are web-accessible
     * and go under the public path, everything else goes into the root.
     */
    private function publish(string $tmpDir): void
    {
        $themesDir = $tmpDir . 'themes' . DS;

        if (is_dir($themesDir)) {
            $this->moveDirectory($themesDir, PUBLIC_THEMES_PATH);
        }

        foreach (scandir($tmpDir) ?: [] as $item) {
            if ($item === '.' || $item === '..' || $item === 'themes') {
                continue;
            }

            $source = $tmpDir . $item;
            is_dir($source)
                ? $this->moveDirectory($source . DS, ROOT_PATH . $item . DS)
                : rename($source, ROOT_PATH . $item);
        }
    }

    private function moveDirectory(string $source, string $target): void
    {
        if (! is_dir($target) && ! mkdir($target, 0777, true) && ! is_dir($target)) {
            return;
        }

        foreach (scandir($source) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            is_dir($source . $item)
                ? $this->moveDirectory($source . $item . DS, $target . $item . DS)
                : rename($source . $item, $target . $item);
        }
    }

    private function removeDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        foreach (scandir($directory) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory . $item;
            is_dir($path) ? $this->removeDirectory($path . DS) : unlink($path);
        }

        rmdir($directory);
    }
}
