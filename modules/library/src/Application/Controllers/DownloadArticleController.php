<?php

declare(strict_types=1);

namespace Johncms\Modules\Library\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Library\Domain\Models\LibraryText;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final readonly class DownloadArticleController
{
    public function __construct(
        private ControllerContext $controllerContext,
    ) {
        $this->controllerContext->initModule('library');
    }

    public function __invoke(int $id, string $type): Response
    {
        if (! in_array($type, ['txt', 'fb2'], true)) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        $article = LibraryText::query()->find($id);

        if ($article === null) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        $content = match ($type) {
            'txt' => $this->buildTxt($article),
            'fb2' => $this->buildFb2($article),
        };

        $response = new StreamedResponse(static function () use ($content): void {
            echo $content;
        });
        $response->headers->set('Content-Type', 'application/octet-stream');
        $response->headers->set('Content-Description', 'inline; File Transfer');
        $response->headers->set('Content-Disposition', 'attachment; filename="book' . time() . '.' . $type . '"');
        $response->headers->set('Content-Transfer-Encoding', 'binary');
        $response->headers->set('Content-Length', (string) strlen($content));

        return $response;
    }

    private function buildTxt(LibraryText $article): string
    {
        return $this->htmlToPlainText((string) $article->text);
    }

    /**
     * Converts article HTML to plain text, keeping paragraph breaks as new lines.
     */
    private function htmlToPlainText(string $html): string
    {
        $text = preg_replace('#</p\s*>|<br\s*/?>|</div\s*>|</h[1-6]\s*>#i', PHP_EOL, $html);
        $text = strip_tags((string) $text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim($text);
    }

    private function buildFb2(LibraryText $article): string
    {
        $coverBase64 = '';
        $coverPath = UPLOAD_PATH . 'library/images/orig/' . $article->id . '.png';

        if (file_exists($coverPath)) {
            $coverBase64 = chunk_split(base64_encode((string) file_get_contents($coverPath)));
        }

        $plain = $this->htmlToPlainText((string) $article->text);
        $paragraphs = preg_split('/\R+/u', $plain) ?: [];
        $body = '';
        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);
            $body .= $paragraph === ''
                ? '<empty-line/>' . PHP_EOL
                : '<p>' . htmlspecialchars($paragraph, ENT_XML1) . '</p>' . PHP_EOL;
        }

        $out = '<?xml version="1.0" encoding="utf-8"?>' . PHP_EOL
            . '<FictionBook xmlns="http://www.gribuser.ru/xml/fictionbook/2.0" xmlns:l="http://www.w3.org/1999/xlink">' . PHP_EOL
            . '<description>' . PHP_EOL
            . '<title-info>' . PHP_EOL
            . '<genre>sf_history</genre>' . PHP_EOL
            . '<author><first-name>' . __('Author name') . '</first-name><last-name>' . __('Author last name') . '</last-name></author>' . PHP_EOL
            . '<book-title>' . htmlspecialchars($article->name, ENT_XML1) . '</book-title>' . PHP_EOL
            . '<date>' . __('Date') . '</date>' . PHP_EOL;

        if ($coverBase64) {
            $out .= '<coverpage><image l:href="#cover.png"/></coverpage>' . PHP_EOL;
        }

        $out .= '<lang>ru</lang>' . PHP_EOL
            . '</title-info>' . PHP_EOL
            . '<document-info>' . PHP_EOL
            . '<author><nickname></nickname></author>' . PHP_EOL
            . '<program-used>Lib converter jcms</program-used>' . PHP_EOL
            . '<id></id>' . PHP_EOL
            . '<version>1.0</version>' . PHP_EOL
            . '</document-info>' . PHP_EOL
            . '</description>' . PHP_EOL
            . '<body><title><p>' . htmlspecialchars($article->name, ENT_XML1) . '</p></title>' . PHP_EOL
            . '<section>' . $body . '</section>' . PHP_EOL
            . '</body>' . PHP_EOL;

        if ($coverBase64) {
            $out .= '<binary id="cover.png" content-type="image/png">' . PHP_EOL
                . $coverBase64
                . '</binary>' . PHP_EOL;
        }

        $out .= '</FictionBook>';

        return $out;
    }
}
