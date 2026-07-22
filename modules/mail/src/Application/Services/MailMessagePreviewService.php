<?php

declare(strict_types=1);

namespace Johncms\Modules\Mail\Application\Services;

use Johncms\Smilies\SmiliesRendererInterface;
use Simba77\EmbedMedia\Embed;

/**
 * Builds the HTML preview of the last message shown in the conversation lists.
 */
final readonly class MailMessagePreviewService
{
    private const PREVIEW_LIMIT = 500;

    public function __construct(
        private SmiliesRendererInterface $smiliesRenderer,
        private \HTMLPurifier $purifier,
        private Embed $media,
    ) {
    }

    public function render(string $rawText, int $contactId, bool $authorIsAdmin): string
    {
        if (trim($rawText) === '') {
            return '';
        }

        $smileMode = $authorIsAdmin ? 1 : 0;

        if (mb_strlen($rawText) > self::PREVIEW_LIMIT) {
            $plain = trim(
                html_entity_decode(
                    strip_tags($this->purifier->purify($rawText)),
                    ENT_QUOTES | ENT_HTML5,
                    'UTF-8'
                )
            );
            $plain = mb_substr($plain, 0, self::PREVIEW_LIMIT);
            $preview = $this->smiliesRenderer->render(htmlspecialchars($plain, ENT_QUOTES, 'UTF-8'), $smileMode);

            return $preview . '...<a href="/mail/write/' . $contactId . '">' . __('Continue') . ' &gt;&gt;</a>';
        }

        $html = $this->purifier->purify($rawText);
        $html = $this->media->embedMedia($html);

        return $this->smiliesRenderer->render($html, $smileMode);
    }
}
