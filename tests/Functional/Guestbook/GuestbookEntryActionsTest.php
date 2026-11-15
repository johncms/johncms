<?php

declare(strict_types=1);

namespace Tests\Functional\Guestbook;

use Dom\Element;
use Dom\HTMLDocument;
use Johncms\Users\User;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

/**
 * The row under an entry: the buttons of whoever may act on it, and where it came from in the
 * button of the staff, as under a comment.
 */
final class GuestbookEntryActionsTest extends FunctionalTestCase
{
    public function testTheStaffActsFromButtonsAndSeesTheOriginBehindTheShield(): void
    {
        $id = Fixture::insert('guest', ['adm' => 0, 'name' => 'Visitor', 'text' => '<p>Message</p>', 'time' => time(), 'ip' => 2130706433, 'browser' => 'Test browser']);

        $entry = $this->entry($id, FunctionalUserFactory::createSupervisor());

        self::assertNotNull($entry->querySelector('.site-comment__actions a[href="/guestbook/edit?id=' . $id . '"]'));
        self::assertNotNull($entry->querySelector('.site-comment__actions form[action="/guestbook/delpost"] button.site-comment__delete'));
        $origin = $entry->querySelector('.site-comment__actions details.site-meta');
        self::assertNotNull($origin, 'Where the entry came from is behind the button of the staff.');
        self::assertStringContainsString('127.0.0.1', $origin->textContent);
        self::assertStringContainsString('Test browser', $origin->textContent);
        self::assertNull($entry->querySelector('details.site-popover:not(.site-meta)'), 'Nothing is hidden in a menu any more.');
    }

    public function testAMemberSeesNeitherTheButtonsOfTheStaffNorTheOrigin(): void
    {
        $id = Fixture::insert('guest', ['adm' => 0, 'name' => 'Visitor', 'text' => '<p>Message</p>', 'time' => time(), 'ip' => 2130706433, 'browser' => 'Test browser']);

        $entry = $this->entry($id, FunctionalUserFactory::create());

        self::assertNull($entry->querySelector('a[href^="/guestbook/edit"]'));
        self::assertNull($entry->querySelector('form[action="/guestbook/delpost"]'));
        self::assertNull($entry->querySelector('.site-meta'));
        self::assertStringNotContainsString('127.0.0.1', $entry->textContent);
        self::assertNotNull($entry->querySelector('[data-comment-reply]'), 'A member answers the entry of a guest.');
    }

    private function entry(int $id, User $visitor): Element
    {
        $page = HTMLDocument::createFromString((string) $this->handleRequest('/guestbook/', cookies: $this->actingAs($visitor))->getContent(), LIBXML_NOERROR);
        $entry = $page->querySelector('#guestbook-entry-' . $id);
        self::assertNotNull($entry);

        return $entry;
    }
}
