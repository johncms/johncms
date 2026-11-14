<?php

declare(strict_types=1);

namespace Tests\Functional\Guestbook;

use Dom\HTMLDocument;
use Johncms\Modules\Guestbook\Domain\Models\GuestbookEntry;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

/**
 * Removing an entry and clearing the guestbook: asked in the dialog of the page, carried out only
 * with its answer.
 */
final class GuestbookDeletionTest extends FunctionalTestCase
{
    public function testAnEntryIsDeletedOnlyWithTheAnswerOfTheDialog(): void
    {
        $moderator = FunctionalUserFactory::createSupervisor();
        $id = Fixture::insert('guest', ['adm' => 0, 'name' => 'Visitor', 'text' => 'Message', 'time' => time()]);

        $page = HTMLDocument::createFromString((string) $this->handleRequest('/guestbook/', cookies: $this->actingAs($moderator))->getContent(), LIBXML_NOERROR);
        $form = $page->querySelector('#guestbook-entry-' . $id . ' form[action="/guestbook/delpost"][data-confirm]');
        self::assertNotNull($form, 'The entry is deleted by a form that asks first, not by a link.');
        self::assertSame((string) $id, $form->querySelector('input[name="id"]')?->getAttribute('value'));

        $this->handleRequest('/guestbook/delpost?id=' . $id, cookies: $this->actingAs($moderator));
        self::assertTrue(GuestbookEntry::query()->whereKey($id)->exists(), 'Opening the address deletes nothing.');

        $unconfirmed = $this->postForm('/guestbook/delpost', ['id' => (string) $id], $moderator);
        self::assertSame(409, $unconfirmed->getStatusCode(), 'Without the answer of the dialog nothing is deleted.');
        self::assertTrue(GuestbookEntry::query()->whereKey($id)->exists());

        $deleted = $this->postForm('/guestbook/delpost', ['id' => (string) $id, 'confirmed' => '1'], $moderator);
        self::assertSame(302, $deleted->getStatusCode());
        self::assertFalse(GuestbookEntry::query()->whereKey($id)->exists());
    }

    public function testTheGuestbookIsClearedForThePeriodChosenInTheDialog(): void
    {
        $moderator = FunctionalUserFactory::createSupervisor();
        $old = Fixture::insert('guest', ['adm' => 0, 'name' => 'Visitor', 'text' => 'Old', 'time' => time() - 2 * 86400]);
        $fresh = Fixture::insert('guest', ['adm' => 0, 'name' => 'Visitor', 'text' => 'Fresh', 'time' => time()]);

        $html = (string) $this->handleRequest('/guestbook/', cookies: $this->actingAs($moderator))->getContent();
        $page = HTMLDocument::createFromString($html, LIBXML_NOERROR);
        $form = $page->querySelector('form[action="/guestbook/clean"][data-confirm]');
        self::assertNotNull($form);
        $fields = $page->querySelector('template#' . $form->getAttribute('data-confirm-fields'));
        self::assertNotNull($fields, 'The period is chosen in the dialog.');
        self::assertStringContainsString('name="cl" value="1"', $fields->innerHTML);

        $this->postForm('/guestbook/clean', ['cl' => '2'], $moderator);
        self::assertSame(2, GuestbookEntry::query()->count(), 'Without the answer of the dialog nothing is cleared.');

        $this->postForm('/guestbook/clean', ['cl' => '1', 'confirmed' => '1'], $moderator);
        self::assertFalse(GuestbookEntry::query()->whereKey($old)->exists());
        self::assertTrue(GuestbookEntry::query()->whereKey($fresh)->exists());
    }
}
