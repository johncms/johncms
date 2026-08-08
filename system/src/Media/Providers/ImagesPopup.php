<?php

declare(strict_types=1);

namespace Johncms\Media\Providers;

use DiDom\Document;
use Exception;
use Simba77\EmbedMedia\EmbedProvider;

/**
 * Wraps every image of a text in a link that opens it in a viewer.
 */
class ImagesPopup implements EmbedProvider
{
    public function parse(string $content): string
    {
        // DOMDocument raises a ValueError on an empty string, which is not an exception and
        // would take the page down with it.
        if (trim($content) === '') {
            return $content;
        }

        try {
            $document = new Document($content);
            $images = $document->find('figure.image');

            foreach ($images as $image) {
                $img = $image->first('img');

                if ($img === null) {
                    continue;
                }

                $source = $this->attribute($img->getAttribute('src', ''));
                $title = $this->attribute($img->getAttribute('alt', ''));

                $image->setInnerHtml(
                    '<a class="image-preview"'
                    . ' data-source="' . $source . '"'
                    . ' title="' . $title . '"'
                    . ' href="' . $source . '">' . $image->innerHtml() . '</a>'
                );
            }

            return $this->fragment($document);
        } catch (Exception) {
        }

        return $content;
    }

    /**
     * The text is a fragment of a page, not a document: libxml wraps it in html/body while
     * parsing, and dumping the document as is would put that wrapper into the page.
     */
    private function fragment(Document $document): string
    {
        $body = $document->first('body');

        return $body === null ? $document->html() : $body->innerHtml();
    }

    /**
     * The value goes into an attribute of markup built by hand, so it is escaped here — an alt
     * text carrying a quote would otherwise end the attribute and let the rest of it through.
     */
    private function attribute(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
