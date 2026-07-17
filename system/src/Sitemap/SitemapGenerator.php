<?php

declare(strict_types=1);

namespace Johncms\Sitemap;

final readonly class SitemapGenerator
{
    private const MAX_URLS_PER_FILE = 45000;

    /**
     * @param iterable<SitemapUrlProviderInterface> $moduleProviders
     */
    public function __construct(
        private CoreUrlsProvider $coreUrlsProvider,
        private iterable $moduleProviders,
        private RobotsTxtUpdater $robotsTxtUpdater,
    ) {
    }

    public function generate(): void
    {
        $lockHandle = fopen(CACHE_PATH . 'sitemap.lock', 'c+');
        if ($lockHandle === false) {
            throw new \RuntimeException('Failed to open sitemap lock file.');
        }

        if (! flock($lockHandle, LOCK_EX | LOCK_NB)) {
            fclose($lockHandle);
            return;
        }

        try {
            $homeUrl = $this->normalizeHomeUrl((string) config('johncms.homeurl', ''));
            $generatedChunkFiles = [];

            $groups = [
                'core'  => $this->coreUrlsProvider->getEntries($homeUrl),
            ];

            foreach ($this->moduleProviders as $provider) {
                if (! $provider instanceof SitemapUrlProviderInterface) {
                    continue;
                }

                $groupName = $provider->groupName();
                if (isset($groups[$groupName])) {
                    throw new \RuntimeException('Duplicate sitemap group name: ' . $groupName);
                }

                $groups[$groupName] = $provider->getEntries($homeUrl);
            }

            foreach ($groups as $groupName => $entries) {
                $generatedChunkFiles = array_merge(
                    $generatedChunkFiles,
                    $this->writeGroupChunks($groupName, $entries)
                );
            }

            $this->cleanupStaleChunks($generatedChunkFiles);

            $indexXml = $this->buildSitemapIndexXml($homeUrl, $generatedChunkFiles);
            $this->atomicWrite(PUBLIC_PATH . 'sitemap.xml', $indexXml);

            $this->robotsTxtUpdater->update($homeUrl);
        } finally {
            flock($lockHandle, LOCK_UN);
            fclose($lockHandle);
        }
    }

    /**
     * @param iterable<SitemapUrlEntry> $entries
     * @return array<int, string>
     */
    private function writeGroupChunks(string $groupName, iterable $entries): array
    {
        $generatedFiles = [];
        $chunk = [];
        $chunkIndex = 1;

        foreach ($entries as $entry) {
            $chunk[] = $entry;
            if (count($chunk) < self::MAX_URLS_PER_FILE) {
                continue;
            }

            $generatedFiles[] = $this->writeChunkFile($groupName, $chunkIndex, $chunk);
            $chunk = [];
            ++$chunkIndex;
        }

        if ($chunk !== []) {
            $generatedFiles[] = $this->writeChunkFile($groupName, $chunkIndex, $chunk);
        }

        return $generatedFiles;
    }

    /**
     * @param array<int, SitemapUrlEntry> $entries
     */
    private function writeChunkFile(string $groupName, int $chunkIndex, array $entries): string
    {
        $filename = sprintf('sitemap-%s-%d.xml', $groupName, $chunkIndex);
        $xml = $this->buildUrlSetXml($entries);

        $this->atomicWrite(PUBLIC_PATH . $filename, $xml);

        return $filename;
    }

    /**
     * @param array<int, SitemapUrlEntry> $entries
     */
    private function buildUrlSetXml(array $entries): string
    {
        $xml = [
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
        ];

        foreach ($entries as $entry) {
            $xml[] = '  <url>';
            $xml[] = '    <loc>' . $this->escapeXml($entry->loc) . '</loc>';
            if ($entry->lastmod !== null) {
                $xml[] = '    <lastmod>' . $this->escapeXml($entry->lastmod) . '</lastmod>';
            }
            $xml[] = '  </url>';
        }

        $xml[] = '</urlset>';

        return implode(PHP_EOL, $xml) . PHP_EOL;
    }

    /**
     * @param array<int, string> $chunkFiles
     */
    private function buildSitemapIndexXml(string $homeUrl, array $chunkFiles): string
    {
        $xml = [
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
        ];

        foreach ($chunkFiles as $chunkFile) {
            $xml[] = '  <sitemap>';
            $xml[] = '    <loc>' . $this->escapeXml($homeUrl . '/' . $chunkFile) . '</loc>';
            $xml[] = '  </sitemap>';
        }

        $xml[] = '</sitemapindex>';

        return implode(PHP_EOL, $xml) . PHP_EOL;
    }

    /**
     * @param array<int, string> $generatedChunkFiles
     */
    private function cleanupStaleChunks(array $generatedChunkFiles): void
    {
        $existingChunkPaths = glob(PUBLIC_PATH . 'sitemap-*.xml') ?: [];

        $expectedPaths = [];
        foreach ($generatedChunkFiles as $filename) {
            $expectedPaths[] = PUBLIC_PATH . $filename;
        }

        foreach ($existingChunkPaths as $chunkPath) {
            if (in_array($chunkPath, $expectedPaths, true)) {
                continue;
            }

            @unlink($chunkPath);
        }
    }

    private function normalizeHomeUrl(string $homeUrl): string
    {
        $normalized = rtrim(trim($homeUrl), '/');
        if ($normalized === '') {
            throw new \RuntimeException('johncms.homeurl is empty.');
        }

        return $normalized;
    }

    private function escapeXml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
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
