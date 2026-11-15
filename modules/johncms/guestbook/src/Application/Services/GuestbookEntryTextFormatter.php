<?php

declare(strict_types=1);

namespace Johncms\Modules\Guestbook\Application\Services;

use Johncms\Auth\Authorization\StaffTitles;
use Johncms\Content\ContentContext;
use Johncms\Content\ContentRendererInterface;
use Johncms\Modules\Guestbook\Domain\Models\GuestbookEntry;
use Twig\Markup;

final readonly class GuestbookEntryTextFormatter
{
    public function __construct(
        private StaffTitles $staffTitles,
        private ContentRendererInterface $content,
    ) {
    }

    /**
     * The text of an entry: sanitized, with the media embedded and the smilies rendered. It is
     * markup by contract — everything unsafe has been taken out of it by the sanitizer.
     */
    public function formatPost(GuestbookEntry $entry): Markup
    {
        return $this->content->render(
            $entry->text,
            new ContentContext(adminSmilies: $this->staffTitles->isStaff((int) $entry->user_id))
        );
    }
}
