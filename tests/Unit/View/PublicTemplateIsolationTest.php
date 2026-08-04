<?php

declare(strict_types=1);

namespace Tests\Unit\View;

use FilesystemIterator;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * The admin panel and the public site share one Twig environment, so nothing stops a public page
 * from extending an admin layout by accident. This catches the typo; the actual boundary is the
 * access middleware on the routes, not the visibility of a template.
 *
 * Only one direction is checked. An admin template extending a public one is allowed and useful:
 * it is how the admin variant of a page reuses everything but its chrome.
 */
final class PublicTemplateIsolationTest extends TestCase
{
    public function testNoPublicTemplateReferencesTheAdminNamespace(): void
    {
        $offenders = [];

        foreach ($this->publicTemplates() as $file) {
            if (str_contains((string) file_get_contents($file), '@admin/')) {
                $offenders[] = $file;
            }
        }

        self::assertSame(
            [],
            $offenders,
            'Public templates must not reference the @admin namespace: ' . implode(', ', $offenders)
        );
    }

    /**
     * @return array<string>
     */
    private function publicTemplates(): array
    {
        $files = [];

        foreach ((array) glob(THEMES_PATH . '*' . DS . 'templates', GLOB_ONLYDIR) as $directory) {
            foreach ($this->templatesUnder((string) $directory) as $file) {
                if (! str_contains($file, DS . 'templates' . DS . 'admin' . DS)) {
                    $files[] = $file;
                }
            }
        }

        foreach ((array) glob(MODULES_PATH . '*' . DS . 'templates' . DS . 'public', GLOB_ONLYDIR) as $directory) {
            $files = array_merge($files, $this->templatesUnder((string) $directory));
        }

        return $files;
    }

    /**
     * @return array<string>
     */
    private function templatesUnder(string $directory): array
    {
        if (! is_dir($directory)) {
            return [];
        }

        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'twig') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }
}
