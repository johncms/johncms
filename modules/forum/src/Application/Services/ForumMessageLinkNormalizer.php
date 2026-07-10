<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Services;

/**
 * Fixes legacy forum message links produced by an old conversion:
 *  - unwraps the "/redirect/?url=" interstitial for the site's own (internal) links,
 *    turning them into plain relative links (external links stay wrapped);
 *  - rewrites legacy single-post links ("?act=show_post&id=N") to the canonical
 *    "/forum/post/N/" route so they no longer bounce through a 301 redirect;
 *  - rewrites legacy topic links ("?type=topic&id=N") to their SEO url, when a
 *    topic url resolver is provided.
 */
final class ForumMessageLinkNormalizer
{
    /**
     * @param string[] $internalHosts   Host names treated as the site's own (e.g. ["johncms.com"]).
     * @param (callable(int $topicId, ?int $page): ?string)|null $topicUrlResolver
     *        Builds the SEO url for a legacy topic link, or returns null when the topic is gone.
     */
    public function normalize(string $text, array $internalHosts, ?callable $topicUrlResolver = null): string
    {
        $hosts = [];
        foreach ($internalHosts as $host) {
            $host = $this->stripWww(strtolower(trim($host)));
            if ($host !== '') {
                $hosts[] = $host;
            }
        }

        // 1. Unwrap redirect-wrapped internal links; keep external links wrapped.
        $text = preg_replace_callback(
            '~https?://[^"\'\s<>]*?/redirect/\?url=([^"\'\s<>]+)~i',
            function (array $matches) use ($hosts, $topicUrlResolver): string {
                $inner = rawurldecode($matches[1]);
                $host = parse_url($inner, PHP_URL_HOST);
                if (! is_string($host) || ! in_array($this->stripWww(strtolower($host)), $hosts, true)) {
                    return $matches[0];
                }

                return $this->toDirectInternalUrl($inner, $topicUrlResolver);
            },
            $text
        );

        // 2. Normalize any remaining non-wrapped legacy single-post links.
        return preg_replace(
            '~forum/(?:index\.php)?\?act=show_post&(?:amp;)?id=(\d+)~i',
            'forum/post/$1/',
            $text
        );
    }

    /**
     * @param (callable(int $topicId, ?int $page): ?string)|null $topicUrlResolver
     */
    private function toDirectInternalUrl(string $internalUrl, ?callable $topicUrlResolver): string
    {
        $parts = parse_url($internalUrl);
        // Drop the legacy front controller from the path: /forum/index.php -> /forum/.
        $path = preg_replace('~/index\.php$~', '/', $parts['path'] ?? '/');
        $query = $parts['query'] ?? '';

        if ($path === '/forum/' && $query !== '') {
            parse_str($query, $params);
            $id = isset($params['id']) && ctype_digit((string) $params['id']) ? (int) $params['id'] : null;

            // Legacy single post -> canonical route.
            if ($id !== null && ($params['act'] ?? null) === 'show_post') {
                return '/forum/post/' . $id . '/';
            }

            // Legacy topic -> SEO url built from the topic itself.
            if ($id !== null && ($params['type'] ?? null) === 'topic' && $topicUrlResolver !== null) {
                $page = isset($params['page']) && ctype_digit((string) $params['page']) ? (int) $params['page'] : null;
                $resolved = $topicUrlResolver($id, $page);
                if ($resolved !== null) {
                    return $resolved;
                }
            }
        }

        $fragment = isset($parts['fragment']) ? '#' . $parts['fragment'] : '';

        // Keep the value valid inside an HTML attribute.
        return str_replace('&', '&amp;', $path . ($query !== '' ? '?' . $query : '') . $fragment);
    }

    private function stripWww(string $host): string
    {
        return preg_replace('~^www\.~', '', $host);
    }
}
