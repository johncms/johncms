<?php

declare(strict_types=1);

namespace Johncms\Sitemap;

final class RobotsTxtUpdater
{
    public function update(string $homeUrl): void
    {
        $robotsPath = PUBLIC_PATH . 'robots.txt';
        $sitemapLine = 'Sitemap: ' . $homeUrl . '/sitemap.xml';

        $lines = [];
        if (is_file($robotsPath)) {
            $content = file_get_contents($robotsPath);
            if ($content !== false) {
                $lines = preg_split('/\r\n|\r|\n/', $content) ?: [];
            }
        }

        $result = [];
        foreach ($lines as $line) {
            if ($line === null) {
                continue;
            }

            if (stripos(trim($line), 'Sitemap:') === 0) {
                continue;
            }

            $result[] = rtrim($line, "\r\n");
        }

        while ($result !== [] && trim((string) end($result)) === '') {
            array_pop($result);
        }

        $result[] = $sitemapLine;

        $payload = implode(PHP_EOL, $result) . PHP_EOL;
        $this->atomicWrite($robotsPath, $payload);
    }

    private function atomicWrite(string $path, string $content): void
    {
        $tempPath = $path . '.tmp';
        if (file_put_contents($tempPath, $content, LOCK_EX) === false) {
            throw new \RuntimeException('Failed to write temporary file: ' . $tempPath);
        }

        if (! rename($tempPath, $path)) {
            @unlink($tempPath);
            throw new \RuntimeException('Failed to move temporary file into place: ' . $path);
        }
    }
}
