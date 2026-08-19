<?php

declare(strict_types=1);

namespace Johncms\Content;

use Twig\Markup;

/**
 * Turns the text a user wrote into the markup a page shows.
 *
 * One call replaces the chain every caller used to repeat — clean the HTML, embed the media,
 * render the smilies — and the steps in between are an extension point rather than a fixed
 * list: see ContentTransformerInterface.
 */
interface ContentRendererInterface
{
    /**
     * The finished markup. It has been through the sanitizer, so a template prints it as it is.
     */
    public function render(string $html, ContentContext $context = new ContentContext()): Markup;

    /**
     * The same, and null when there is nothing left to show.
     *
     * Markup is an object and an object is truthy however empty it is, so a template cannot ask
     * "is there a text?" about the result of render(). A source that can have nothing to show
     * uses this one.
     */
    public function renderOrNull(string $html, ContentContext $context = new ContentContext()): ?Markup;

    /**
     * The same content stripped to text — for previews, titles and notifications. The media and
     * the smilies are rendered first, so what a transformer removed does not resurface as text.
     */
    public function toPlainText(string $html, ContentContext $context = new ContentContext()): string;
}
