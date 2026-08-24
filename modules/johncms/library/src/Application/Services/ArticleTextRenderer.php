<?php

declare(strict_types=1);

namespace Johncms\Modules\Library\Application\Services;

use Johncms\Content\ContentContext;
use Johncms\Content\ContentRendererInterface;
use Johncms\Content\Html\HtmlFragment;
use Twig\Markup;

final readonly class ArticleTextRenderer
{
    private const PAGE_SIZE = 7000;

    public function __construct(
        private ContentRendererInterface $content,
        private HtmlFragment $fragment,
    ) {
    }

    /**
     * Splits the article HTML into pages on top-level block boundaries.
     *
     * @return string[] List of page HTML fragments (at least one element).
     */
    public function splitIntoPages(string $html): array
    {
        $html = trim($html);
        if ($html === '') {
            return [''];
        }

        $document = $this->fragment->parse($html);

        $pages = [];
        $current = '';
        $currentLength = 0;

        foreach ($this->fragment->topLevelNodes($document) as $node) {
            $chunk = $document->saveHtml($node);
            if (trim($chunk) === '') {
                continue;
            }

            $chunkLength = mb_strlen($chunk);
            if ($current !== '' && $currentLength + $chunkLength > self::PAGE_SIZE) {
                $pages[] = $current;
                $current = '';
                $currentLength = 0;
            }

            $current .= $chunk;
            $currentLength += $chunkLength;
        }

        if ($current !== '') {
            $pages[] = $current;
        }

        return $pages !== [] ? $pages : [''];
    }

    /**
     * Sanitizes and prepares a page fragment for output. The result is markup by contract: it has
     * been through the sanitizer, so a template prints it as it is.
     */
    public function renderPage(string $pageHtml, bool $isAdmin): Markup
    {
        return $this->content->render($pageHtml, new ContentContext(adminSmilies: $isAdmin));
    }
}
