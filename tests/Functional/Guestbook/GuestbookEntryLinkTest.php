<?php

declare(strict_types=1);

namespace Tests\Functional\Guestbook;

use Dom\HTMLDocument;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

/**
 * The lasting address of an entry: it names no page and finds the one the entry is on when opened.
 */
final class GuestbookEntryLinkTest extends FunctionalTestCase
{
    public function testTheLinkLeadsToThePageTheEntryIsOnNow(): void
    {
        $oldest = Fixture::insert('guest', ['adm' => 0, 'name' => 'Visitor', 'text' => 'Oldest', 'time' => time() - 100]);
        for ($i = 0; $i < 9; $i++) {
            Fixture::insert('guest', ['adm' => 0, 'name' => 'Visitor', 'text' => 'Message ' . $i, 'time' => time() - 50]);
        }

        $first = $this->handleRequest('/guestbook/entry/' . $oldest);
        self::assertSame(302, $first->getStatusCode());
        self::assertSame('/guestbook/#guestbook-entry-' . $oldest, $first->headers->get('Location'), 'Ten entries fit on the first page.');

        Fixture::insert('guest', ['adm' => 0, 'name' => 'Visitor', 'text' => 'Newest', 'time' => time()]);

        $later = $this->handleRequest('/guestbook/entry/' . $oldest);
        self::assertSame('/guestbook/?page=2#guestbook-entry-' . $oldest, $later->headers->get('Location'), 'A new entry pushes it to the next page.');
    }

    public function testEntriesOfTheSameSecondAreCountedInTheOrderOfTheFeed(): void
    {
        $time = time();
        $ids = [];
        for ($i = 0; $i < 11; $i++) {
            $ids[] = Fixture::insert('guest', ['adm' => 0, 'name' => 'Visitor', 'text' => 'Message ' . $i, 'time' => $time]);
        }

        // The later of two entries of one second is shown first: the first one written is the last.
        self::assertSame('/guestbook/?page=2#guestbook-entry-' . $ids[0], $this->handleRequest('/guestbook/entry/' . $ids[0])->headers->get('Location'));
        self::assertSame('/guestbook/#guestbook-entry-' . $ids[1], $this->handleRequest('/guestbook/entry/' . $ids[1])->headers->get('Location'));

        $page = HTMLDocument::createFromString((string) $this->handleRequest('/guestbook/?page=2')->getContent(), LIBXML_NOERROR);
        self::assertNotNull($page->querySelector('#guestbook-entry-' . $ids[0]));
    }

    public function testTheDateOfAnEntryIsItsLink(): void
    {
        $id = Fixture::insert('guest', ['adm' => 0, 'name' => 'Visitor', 'text' => 'Message', 'time' => time()]);

        $page = HTMLDocument::createFromString((string) $this->handleRequest('/guestbook/')->getContent(), LIBXML_NOERROR);
        self::assertNotNull($page->querySelector('#guestbook-entry-' . $id . ' a.site-comment__date[href="/guestbook/entry/' . $id . '"]'));
    }

    public function testAnEntryOfTheAdminClubOpensTheClubForTheStaffAndDoesNotExistForOthers(): void
    {
        $id = Fixture::insert('guest', ['adm' => 1, 'name' => 'Admin', 'text' => 'Of the club', 'time' => time()]);

        self::assertSame(404, $this->handleRequest('/guestbook/entry/' . $id)->getStatusCode());
        self::assertSame(404, $this->handleRequest('/guestbook/entry/' . $id, cookies: $this->actingAs(FunctionalUserFactory::create()))->getStatusCode());

        $cookies = $this->actingAs(FunctionalUserFactory::createSupervisor());
        $response = $this->handleRequest('/guestbook/entry/' . $id, cookies: $cookies);
        self::assertSame('/guestbook/#guestbook-entry-' . $id, $response->headers->get('Location'));

        $page = HTMLDocument::createFromString((string) $this->handleRequest('/guestbook/', cookies: $cookies)->getContent(), LIBXML_NOERROR);
        self::assertNotNull($page->querySelector('#guestbook-entry-' . $id), 'The link switched the visitor to the club.');
    }

    public function testAMissingEntryIsNotFound(): void
    {
        self::assertSame(404, $this->handleRequest('/guestbook/entry/999999')->getStatusCode());
    }
}
