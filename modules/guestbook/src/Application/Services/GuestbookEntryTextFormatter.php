<?php

declare(strict_types=1);

namespace Johncms\Modules\Guestbook\Application\Services;

use Johncms\Auth\Authorization\StaffTitles;
use Johncms\Modules\Guestbook\Domain\Models\GuestbookEntry;
use Johncms\Security\HtmlSanitizerInterface;
use Johncms\Smilies\SmiliesRendererInterface;
use Simba77\EmbedMedia\Embed;
use Twig\Markup;

final readonly class GuestbookEntryTextFormatter
{
    public function __construct(
        private StaffTitles $staffTitles,
        private HtmlSanitizerInterface $sanitizer,
        private Embed $media,
        private SmiliesRendererInterface $smiliesRenderer,
    ) {
    }

    /**
     * The text of an entry: sanitized, with the media embedded and the smilies rendered. It is
     * markup by contract — everything unsafe has been taken out of it by the sanitizer.
     */
    public function formatPost(GuestbookEntry $entry): Markup
    {
        $text = $this->media->embedMedia($this->sanitizer->sanitize($entry->text));

        return new Markup(
            $this->smiliesRenderer->render($text, $this->staffTitles->isStaff((int) $entry->user_id)),
            'UTF-8'
        );
    }

    /**
     * The reply of the staff to an entry, or null when there is none: markup is an object and an
     * object is truthy however empty it is, so "no reply" has to be a value of its own.
     */
    public function formatReply(GuestbookEntry $entry): ?Markup
    {
        if ((string) $entry->otvet === '') {
            return null;
        }

        $text = $this->media->embedMedia($this->sanitizer->sanitize($entry->otvet));

        return new Markup($this->smiliesRenderer->render($text, true), 'UTF-8');
    }
}
