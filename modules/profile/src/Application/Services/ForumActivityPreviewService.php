<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\Services;

use Johncms\Smilies\SmiliesRendererInterface;
use Simba77\EmbedMedia\Embed;

final readonly class ForumActivityPreviewService
{
    public function __construct(
        private \HTMLPurifier $purifier,
        private Embed $media,
        private SmiliesRendererInterface $smiliesRenderer,
    ) {
    }

    /**
     * Build a short plain-text preview of a forum post.
     */
    public function make(string $rawText, bool $authorIsStaff): string
    {
        $text = $this->purifier->purify($rawText);
        $text = $this->media->embedMedia($text);
        $text = $this->smiliesRenderer->render($text, $authorIsStaff);
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = trim((string) (preg_replace('/\s+/u', ' ', $text) ?? $text));

        return mb_strimwidth($text, 0, 300, '...');
    }
}
