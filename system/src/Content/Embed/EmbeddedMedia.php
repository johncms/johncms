<?php

declare(strict_types=1);

namespace Johncms\Content\Embed;

/**
 * What a provider recognised in a URL: the template that draws the player and the values it
 * draws it from.
 *
 * A provider returns a template rather than markup on purpose — the look of a player is then a
 * template like any other, which a theme overrides by mirroring its path, and the values go
 * through the escaping of Twig instead of through a concatenation nobody reviews.
 */
final readonly class EmbeddedMedia
{
    /**
     * @param string               $template   A full template name, e.g. `@theme/content/embeds/youtube.twig`.
     * @param array<string, mixed> $parameters The data of that template.
     */
    public function __construct(
        public string $template,
        public array $parameters = [],
    ) {
    }
}
