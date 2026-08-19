<?php

declare(strict_types=1);

namespace Johncms\Content\Embed;

/**
 * An extension point: a service that turns the URL of a media site into a player.
 *
 * This is where a new site is added — Rutube, VK, Vimeo — and it is deliberately the smaller
 * half of the job: a provider matches a URL and names a template, it never touches the document
 * the text is parsed into. The DOM work is done once, by OembedTransformer, for all of them.
 *
 * A module registers one by implementing this interface; the container tags it and the registry
 * asks it in turn.
 */
interface EmbedProviderInterface
{
    /**
     * The order the providers are asked in: the higher, the earlier. A module overrides a
     * built-in provider by claiming the same URLs at a higher priority.
     */
    public function priority(): int;

    /**
     * The player of that URL, or null when the URL belongs to another provider.
     */
    public function embed(string $url): ?EmbeddedMedia;
}
