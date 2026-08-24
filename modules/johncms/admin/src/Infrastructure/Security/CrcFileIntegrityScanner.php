<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Infrastructure\Security;

use Johncms\Modules\Admin\Domain\Services\FileIntegrityScannerInterface;

/**
 * Сканер целостности файлов на основе CRC32-снимка скриптовых файлов сайта.
 */
final class CrcFileIntegrityScanner implements FileIntegrityScannerInterface
{
    private const SNAPSHOT_FILE = 'security-scanner-snapshot.cache';
    private const FILE_PATTERN = '#.*\.(php|cgi|pl|perl|php3|php4|php5|php6|phtml|py|htaccess|tpl)$#i';

    public function snapshotExists(): bool
    {
        return file_exists(CACHE_PATH . self::SNAPSHOT_FILE);
    }

    public function createSnapshot(): void
    {
        $lines = [];
        foreach ($this->collect() as $path => $crc) {
            $lines[] = $path . '|' . $crc;
        }

        file_put_contents(CACHE_PATH . self::SNAPSHOT_FILE, implode("\r\n", $lines) . "\r\n");
        @chmod(CACHE_PATH . self::SNAPSHOT_FILE, 0666);
    }

    public function scan(): array
    {
        $snapshot = $this->loadSnapshot();
        if ($snapshot === []) {
            return [];
        }

        $changed = [];
        foreach ($this->collect() as $path => $crc) {
            if (array_key_exists($path, $snapshot) && $snapshot[$path] !== $crc) {
                $changed[] = $path;
            }
        }

        return $changed;
    }

    /**
     * Directories covered by the snapshot. The root is scanned without
     * recursion: the directories below it are listed separately.
     *
     * @return array<string, bool> Map of an absolute path to the recursion flag.
     */
    private function scanTargets(): array
    {
        $targets = [
            rtrim(ROOT_PATH, '/')      => false,
            rtrim(CONFIG_PATH, '/')    => true,
            rtrim(DATA_PATH, '/')      => true,
            rtrim(MODULES_PATH, '/')   => true,
            rtrim(ROOT_PATH . 'system', '/') => true,
            rtrim(THEMES_PATH, '/')    => true,
        ];

        // Directories inside the document root. They coincide with the ones
        // above as long as PUBLIC_PATH equals ROOT_PATH.
        foreach (['assets', 'themes', 'upload', 'install'] as $folder) {
            $targets[rtrim(PUBLIC_PATH . $folder, '/')] = true;
        }

        return $targets;
    }

    /**
     * @return array<string, string> Карта путь → CRC текущих файлов.
     */
    private function collect(): array
    {
        $files = [];
        foreach ($this->scanTargets() as $directory => $recursive) {
            $this->scanDirectory($directory, $files, $recursive);
        }

        return $files;
    }

    /**
     * @param array<string, string> $files
     */
    private function scanDirectory(string $dir, array &$files, bool $recursive): void
    {
        $handle = @opendir($dir);
        if ($handle === false) {
            return;
        }

        while (($file = readdir($handle)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $fullPath = $dir . '/' . $file;
            if (is_dir($fullPath)) {
                if ($recursive) {
                    $this->scanDirectory($fullPath, $files, true);
                }
                continue;
            }

            if (preg_match(self::FILE_PATTERN, $file)) {
                $relative = str_replace(rtrim(ROOT_PATH, '/'), '.', $dir) . '/' . $file;
                $files[$relative] = strtoupper(dechex(crc32((string) file_get_contents($fullPath))));
            }
        }

        closedir($handle);
    }

    /**
     * @return array<string, string>
     */
    private function loadSnapshot(): array
    {
        $path = CACHE_PATH . self::SNAPSHOT_FILE;
        if (! file_exists($path)) {
            return [];
        }

        $snapshot = [];
        foreach (file($path) ?: [] as $line) {
            $parts = explode('|', trim($line));
            if (count($parts) === 2) {
                $snapshot[$parts[0]] = $parts[1];
            }
        }

        return $snapshot;
    }
}
