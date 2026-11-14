<?php

declare(strict_types=1);

namespace Johncms\EditorUploads;

use Johncms\Content\Html\HtmlFragment;
use Johncms\Http\SiteBaseUrl;

/**
 * The pictures a text shows, as keys to compare with the addresses of stored files.
 *
 * Only the source of an `<img>` counts: a link to the picture or its address written out as text
 * does not show it. The parser decodes the entities of the attribute, and the address of the site
 * itself is taken off, so an absolute `src` and a relative one name the same file.
 */
final readonly class ImageSources
{
    public function __construct(
        private HtmlFragment $html,
        private SiteBaseUrl $siteBaseUrl,
    ) {
    }

    /**
     * @return list<string>
     */
    public function in(string $html): array
    {
        if ($html === '') {
            return [];
        }

        $keys = [];
        foreach ($this->html->parse($html)->querySelectorAll('img[src]') as $image) {
            $key = $this->key((string) $image->getAttribute('src'));
            if ($key !== null) {
                $keys[$key] = true;
            }
        }

        return array_keys($keys);
    }

    /**
     * The address without the scheme, the query and the fragment, and without the host when it is
     * the site itself. Null for what is not an address of a file.
     */
    public function key(string $url): ?string
    {
        $parts = parse_url(trim($url));
        if ($parts === false || ($parts['path'] ?? '') === '') {
            return null;
        }

        $host = strtolower($parts['host'] ?? '');
        if ($host !== '' && $host === $this->siteHost()) {
            $host = '';
        }

        return $host . rawurldecode($parts['path']);
    }

    private function siteHost(): string
    {
        return strtolower((string) parse_url($this->siteBaseUrl->resolve(), PHP_URL_HOST));
    }
}
