<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Smilies;

/**
 * Replaces smiley codes in a text with their HTML representation.
 *
 * The replacement map is built by the admin panel and stored in a cache file.
 */
final class SmiliesRenderer implements SmiliesRendererInterface
{
    private const CACHE_FILE = 'smilies-list.cache';

    /** @var array{usr: array<string, string>, adm: array<string, string>}|null */
    private ?array $smilies = null;

    public function render(string $text, bool $withAdminSmilies = false): string
    {
        $map = $this->map($withAdminSmilies);

        return $map === [] ? $text : strtr($text, $map);
    }

    public function map(bool $withAdminSmilies = false): array
    {
        $smilies = $this->getSmilies();
        if ($smilies === null) {
            return [];
        }

        return $withAdminSmilies
            ? array_merge($smilies['usr'], $smilies['adm'])
            : $smilies['usr'];
    }

    /**
     * @return array{usr: array<string, string>, adm: array<string, string>}|null
     */
    private function getSmilies(): ?array
    {
        if ($this->smilies !== null) {
            return $this->smilies;
        }

        $file = CACHE_PATH . self::CACHE_FILE;
        if (! is_file($file)) {
            return null;
        }

        $content = file_get_contents($file);
        if ($content === false) {
            return null;
        }

        $smilies = unserialize($content, ['allowed_classes' => false]);
        // Guard against an empty or corrupt cache file (missing usr/adm keys).
        if (! is_array($smilies) || ! isset($smilies['usr'], $smilies['adm'])) {
            return null;
        }

        $this->smilies = $smilies;

        return $this->smilies;
    }
}
