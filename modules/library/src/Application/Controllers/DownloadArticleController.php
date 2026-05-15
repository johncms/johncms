<?php

declare(strict_types=1);

namespace Johncms\Modules\Library\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Library\Domain\Models\LibraryText;
use Johncms\System\Legacy\Bbcode;

final readonly class DownloadArticleController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Bbcode $bbcode,
    ) {
        $this->controllerContext->initModule('library');
    }

    public function __invoke(int $id, string $type): string
    {
        if (! in_array($type, ['txt', 'fb2'], true)) {
            http_response_code(404);
            return '';
        }

        $article = LibraryText::query()->find($id);

        if ($article === null) {
            http_response_code(404);
            return '';
        }

        $content = match ($type) {
            'txt' => $this->buildTxt($article),
            'fb2' => $this->buildFb2($article),
        };

        header('Content-Type: application/octet-stream');
        header('Content-Description: inline; File Transfer');
        header('Content-Disposition: attachment; filename="book' . time() . '.' . $type . '"');
        header('Content-Transfer-Encoding: binary');
        header('Content-Length: ' . strlen($content));

        return $content;
    }

    private function buildTxt(LibraryText $article): string
    {
        return $this->bbcode->notags($article->text);
    }

    private function buildFb2(LibraryText $article): string
    {
        $coverBase64 = '';
        $coverPath = UPLOAD_PATH . 'library/images/orig/' . $article->id . '.png';

        if (file_exists($coverPath)) {
            $coverBase64 = chunk_split(base64_encode((string) file_get_contents($coverPath)));
        }

        $text = $this->bbcode->notags($article->text);
        $body = str_replace(
            '<p></p>',
            '<empty-line/>',
            str_replace(PHP_EOL, '</p>' . PHP_EOL . '<p>', $text)
        );

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
            . '<section><p>' . $body . '</p></section>' . PHP_EOL
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
