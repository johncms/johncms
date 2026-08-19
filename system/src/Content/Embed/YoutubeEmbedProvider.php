<?php

declare(strict_types=1);

namespace Johncms\Content\Embed;

/**
 * The player of a YouTube video.
 *
 * The provider only reads the URL: which hosts and which shapes of address belong to YouTube,
 * and what the player has to be pointed at. How the player looks is the template.
 */
final readonly class YoutubeEmbedProvider implements EmbedProviderInterface
{
    public const TEMPLATE = '@theme/content/embeds/youtube.twig';

    /**
     * The address is compared against the list, so a link that merely mentions youtube.com
     * somewhere in it is not a video.
     */
    private const HOSTS = [
        'youtube.com',
        'www.youtube.com',
        'm.youtube.com',
        'music.youtube.com',
        'youtu.be',
        'www.youtu.be',
    ];

    /**
     * The path shapes that carry the id of a video rather than a listing or a channel.
     */
    private const PATH_PREFIXES = ['embed', 'shorts', 'live', 'v'];

    /**
     * What YouTube itself accepts as an id. The value ends up in a URL of an iframe, so it is
     * matched rather than trusted: the editor writes the address, and an editor is a user.
     */
    private const ID_PATTERN = '/^[A-Za-z0-9_-]{6,20}$/';

    public function priority(): int
    {
        return 0;
    }

    public function embed(string $url): ?EmbeddedMedia
    {
        $parts = parse_url($url);
        if ($parts === false || ! in_array($parts['host'] ?? '', self::HOSTS, true)) {
            return null;
        }

        $query = [];
        if (($parts['query'] ?? '') !== '') {
            parse_str($parts['query'], $query);
        }

        $id = $this->videoId($parts['path'] ?? '', $query);
        if ($id === null) {
            return null;
        }

        return new EmbeddedMedia(
            self::TEMPLATE,
            [
                'src' => 'https://www.youtube.com/embed/' . $id . '?' . http_build_query($this->playerParameters($query)),
            ]
        );
    }

    /**
     * @param array<array-key, mixed> $query
     */
    private function videoId(string $path, array $query): ?string
    {
        $candidate = is_string($query['v'] ?? null) ? $query['v'] : $this->idFromPath($path);

        return $candidate !== null && preg_match(self::ID_PATTERN, $candidate) === 1 ? $candidate : null;
    }

    /**
     * `youtu.be/<id>` puts the id in the first segment; on youtube.com it stands behind one of
     * the prefixes that mean "a single video".
     */
    private function idFromPath(string $path): ?string
    {
        $segments = array_values(array_filter(explode('/', $path), static fn (string $s): bool => $s !== ''));
        if ($segments === []) {
            return null;
        }

        if (in_array($segments[0], self::PATH_PREFIXES, true)) {
            return $segments[1] ?? null;
        }

        return count($segments) === 1 ? $segments[0] : null;
    }

    /**
     * @param array<array-key, mixed> $query
     * @return array<string, int|string>
     */
    private function playerParameters(array $query): array
    {
        // Don't show the videos of other channels once this one ends.
        $parameters = ['rel' => 0];

        $start = $this->startSeconds(is_string($query['t'] ?? null) ? $query['t'] : '');
        if ($start > 0) {
            $parameters['start'] = $start;
        }

        return $parameters;
    }

    /**
     * YouTube writes the moment a link points at either as a number of seconds or as `1h2m3s`,
     * while the player only understands seconds.
     */
    private function startSeconds(string $value): int
    {
        if (ctype_digit($value)) {
            return (int) $value;
        }

        if (preg_match('/^(?:(\d+)h)?(?:(\d+)m)?(?:(\d+)s)?$/', $value, $matches) !== 1) {
            return 0;
        }

        return ((int) ($matches[1] ?? 0)) * 3600 + ((int) ($matches[2] ?? 0)) * 60 + (int) ($matches[3] ?? 0);
    }
}
