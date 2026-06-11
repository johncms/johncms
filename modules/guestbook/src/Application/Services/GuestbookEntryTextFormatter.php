<?php

declare(strict_types=1);

namespace Johncms\Modules\Guestbook\Application\Services;

use HTMLPurifier;
use Johncms\Modules\Guestbook\Domain\Models\GuestbookEntry;
use Johncms\System\Legacy\Tools;
use Simba77\EmbedMedia\Embed;

final readonly class GuestbookEntryTextFormatter
{
    public function __construct(
        private HTMLPurifier $purifier,
        private Embed $media,
        private Tools $tools,
    ) {
    }

    public function formatPost(GuestbookEntry $entry): string
    {
        $text = $this->media->embedMedia($this->purifier->purify($entry->text));
        return $this->tools->smilies($text, $entry->user !== null && $entry->user->rights >= 1);
    }

    public function formatReply(GuestbookEntry $entry): string
    {
        $text = $this->media->embedMedia($this->purifier->purify($entry->otvet));
        return $this->tools->smilies($text, true);
    }
}
