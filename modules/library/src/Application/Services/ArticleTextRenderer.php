<?php

declare(strict_types=1);

namespace Johncms\Modules\Library\Application\Services;

use DOMDocument;
use DOMElement;
use Johncms\Media\MediaEmbed;
use Johncms\Security\HtmlSanitizerInterface;
use Johncms\Smilies\SmiliesRendererInterface;
use Simba77\EmbedMedia\Embed;
use Twig\Markup;

final class ArticleTextRenderer
{
    private const PAGE_SIZE = 7000;

    private HtmlSanitizerInterface $sanitizer;
    private Embed $media;
    private SmiliesRendererInterface $smiliesRenderer;

    public function __construct()
    {
        $this->sanitizer = di(HtmlSanitizerInterface::class);
        $this->media = di(MediaEmbed::class);
        $this->smiliesRenderer = di(SmiliesRendererInterface::class);
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

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML(
            '<meta http-equiv="Content-Type" content="text/html; charset=utf-8"><div id="lib-root">' . $html . '</div>',
            LIBXML_NOERROR | LIBXML_NOWARNING
        );
        libxml_clear_errors();

        $root = null;
        foreach ($dom->getElementsByTagName('div') as $div) {
            if ($div instanceof DOMElement && $div->getAttribute('id') === 'lib-root') {
                $root = $div;
                break;
            }
        }

        if ($root === null) {
            return [$html];
        }

        $pages = [];
        $current = '';
        $currentLength = 0;

        foreach (iterator_to_array($root->childNodes) as $node) {
            $chunk = $dom->saveHTML($node);
            if ($chunk === false || trim($chunk) === '') {
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
        $text = $this->sanitizer->sanitize($pageHtml);
        $text = $this->media->embedMedia($text);

        return new Markup($this->smiliesRenderer->render($text, $isAdmin), 'UTF-8');
    }
}
