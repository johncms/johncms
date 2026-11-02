<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Services;

use Johncms\Content\ContentRendererInterface;
use Twig\Markup;

/**
 * Renders stored source HTML through the shared content pipeline on output.
 */
final readonly class ItemContentFormatter
{
    public function __construct(
        private ContentRendererInterface $content,
    ) {
    }

    /**
     * Sanitized content, or null when there is none. It is markup by contract — a template
     * prints it as it is — and null rather than empty markup, because markup is an object and
     * an object is truthy however empty it is.
     */
    public function format(?string $html): ?Markup
    {
        if ($html === null || $html === '') {
            return null;
        }

        return $this->content->renderOrNull($html);
    }

    public function plain(?string $html): string
    {
        return $html === null || $html === '' ? '' : $this->content->toPlainText($html);
    }
}
