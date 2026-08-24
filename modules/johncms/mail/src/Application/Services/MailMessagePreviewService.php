<?php

declare(strict_types=1);

namespace Johncms\Modules\Mail\Application\Services;

use Johncms\Content\ContentContext;
use Johncms\Content\ContentRendererInterface;
use Johncms\Security\HtmlSanitizerInterface;
use Johncms\Smilies\SmiliesRendererInterface;

/**
 * Builds the HTML preview of the last message shown in the conversation lists.
 */
final readonly class MailMessagePreviewService
{
    private const PREVIEW_LIMIT = 500;

    public function __construct(
        private SmiliesRendererInterface $smiliesRenderer,
        private HtmlSanitizerInterface $sanitizer,
        private ContentRendererInterface $content,
    ) {
    }

    public function render(string $rawText, int $contactId, bool $authorIsAdmin): string
    {
        if (trim($rawText) === '') {
            return '';
        }

        if (mb_strlen($rawText) > self::PREVIEW_LIMIT) {
            // Cut short, the message is shown as text, so it is stripped rather than rendered:
            // the smilies are put back afterwards, over the escaped text, and the media of a
            // message nobody opened yet has nowhere to play.
            $plain = mb_substr($this->sanitizer->toPlainText($rawText), 0, self::PREVIEW_LIMIT);
            $preview = $this->smiliesRenderer->render(htmlspecialchars($plain, ENT_QUOTES, 'UTF-8'), $authorIsAdmin);

            return $preview . '...<a href="/mail/write/' . $contactId . '">' . __('Continue') . ' &gt;&gt;</a>';
        }

        return (string) $this->content->render($rawText, new ContentContext(adminSmilies: $authorIsAdmin));
    }
}
