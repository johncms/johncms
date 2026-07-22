<?php

declare(strict_types=1);

namespace Johncms\Modules\Guestbook\Application\Services;

use HTMLPurifier;
use Johncms\Modules\Guestbook\Domain\Models\GuestbookEntry;
use Johncms\Smilies\SmiliesRendererInterface;
use Simba77\EmbedMedia\Embed;

final readonly class GuestbookEntryTextFormatter
{
    public function __construct(
        private HTMLPurifier $purifier,
        private Embed $media,
        private SmiliesRendererInterface $smiliesRenderer,
    ) {
    }

    public function formatPost(GuestbookEntry $entry): string
    {
        $text = $this->media->embedMedia($this->purifier->purify($entry->text));
        return $this->smiliesRenderer->render($text, $entry->user !== null && $entry->user->rights >= 1);
    }

    public function formatReply(GuestbookEntry $entry): string
    {
        $text = $this->media->embedMedia($this->purifier->purify($entry->otvet));
        return $this->smiliesRenderer->render($text, true);
    }
}
