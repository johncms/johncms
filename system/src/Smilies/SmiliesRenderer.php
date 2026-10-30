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
 */
final class SmiliesRenderer implements SmiliesRendererInterface
{
    /** @var array<string, array<string, string>> The maps of HTML, keyed by whether the staff codes are in. */
    private array $html = [];

    public function __construct(private readonly SmiliesMap $smilies)
    {
    }

    public function render(string $text, bool $withAdminSmilies = false): string
    {
        $map = $this->map($withAdminSmilies);

        return $map === [] ? $text : strtr($text, $map);
    }

    public function map(bool $withAdminSmilies = false): array
    {
        $key = $withAdminSmilies ? 'adm' : 'usr';
        if (isset($this->html[$key])) {
            return $this->html[$key];
        }

        $smilies = $this->smilies->get();
        $paths = $withAdminSmilies ? array_merge($smilies['usr'], $smilies['adm']) : $smilies['usr'];
        $homeUrl = rtrim((string) (config('johncms')['homeurl'] ?? ''), '/');

        return $this->html[$key] = array_map(
            static fn(string $path): string => '<img src="' . htmlspecialchars($homeUrl . '/' . $path) . '" alt="" />',
            $paths
        );
    }
}
