<?php

declare(strict_types=1);

namespace Tests\Functional\Guestbook;

use Dom\HTMLDocument;
use Johncms\Modules\Guestbook\Domain\Models\GuestbookEntry;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

final class GuestbookLiveListTest extends FunctionalTestCase
{
    public function testLivePaginationReturnsRegionsWithoutTheEditor(): void
    {
        $user = FunctionalUserFactory::create();
        for ($i = 0; $i < 25; $i++) {
            Fixture::insert('guest', ['adm' => 0, 'name' => 'Visitor', 'text' => 'Message ' . $i, 'time' => time() - $i]);
        }

        $response = $this->handleRequest(
            uri: '/guestbook/?page=2',
            server: ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'],
            cookies: $this->actingAs($user),
        );
        self::assertSame(200, $response->getStatusCode());
        $html = (string) $response->getContent();
        $document = HTMLDocument::createFromString($html, LIBXML_NOERROR);
        self::assertNotNull($document->querySelector('[data-live-list-region="guestbook-feed"] .site-comment'));
        self::assertNotNull($document->querySelector('[data-live-list-region="guestbook-feedback"]'));
        self::assertNull($document->querySelector('[data-editor]'));
        self::assertStringNotContainsString('<!DOCTYPE', $html);
        self::assertNotNull($document->querySelector('a[href*="#guestbook-feed"]'));

        $firstEntry = $document->querySelector('.site-comment')?->getAttribute('id');
        $response = $this->handleRequest(
            uri: '/guestbook/?page=2',
            method: 'POST',
            parameters: $this->withCsrfToken(['message' => '']),
            server: ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'],
            cookies: $this->actingAs($user),
        );
        $document = HTMLDocument::createFromString((string) $response->getContent(), LIBXML_NOERROR);
        self::assertSame($firstEntry, $document->querySelector('.site-comment')?->getAttribute('id'), 'Validation keeps the current page.');

        $response = $this->handleRequest(uri: '/guestbook/?page=2', cookies: $this->actingAs($user));
        $document = HTMLDocument::createFromString((string) $response->getContent(), LIBXML_NOERROR);
        self::assertSame('/guestbook/?page=2', $document->querySelector('[data-guestbook-form]')?->getAttribute('action'));
    }

    public function testSuccessfulPostRedirectsToTheNewEntryAndValidationKeepsTheForm(): void
    {
        $user = FunctionalUserFactory::create();
        $cookies = $this->actingAs($user);
        $live = ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'];
        $response = $this->handleRequest(
            uri: '/guestbook/',
            method: 'POST',
            parameters: $this->withCsrfToken(['message' => '<p>A live guestbook message</p>']),
            server: $live,
            cookies: $cookies,
        );
        self::assertSame(302, $response->getStatusCode());
        $entry = GuestbookEntry::query()->where('user_id', $user->id)->firstOrFail();
        $response = $this->handleRequest(uri: '/guestbook/', server: $live, cookies: $cookies);
        $document = HTMLDocument::createFromString((string) $response->getContent(), LIBXML_NOERROR);
        self::assertSame('guestbook-entry-' . $entry->id, $document->querySelector('[data-live-list-target]')?->getAttribute('id'));
        self::assertNotNull($document->querySelector('[data-guestbook-added]'));
        self::assertNull($document->querySelector('[data-live-list-region="guestbook-feedback"] .alert'));

        $response = $this->handleRequest(
            uri: '/guestbook/',
            method: 'POST',
            parameters: $this->withCsrfToken(['message' => '']),
            server: $live,
            cookies: $cookies,
        );
        self::assertSame(200, $response->getStatusCode());
        $document = HTMLDocument::createFromString((string) $response->getContent(), LIBXML_NOERROR);
        self::assertNotNull($document->querySelector('[data-live-list-region="guestbook-feedback"] .alert'));
        self::assertNull($document->querySelector('[data-guestbook-added]'));
        self::assertSame(1, GuestbookEntry::query()->where('user_id', $user->id)->count());
    }

    public function testGuestCaptchaRemainsOnPaginationAndRefreshesAfterInvalidSubmission(): void
    {
        $live = ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'];
        $response = $this->handleRequest('/guestbook/');
        self::assertStringContainsString('data-live-list-form', (string) $response->getContent());
        self::assertStringContainsString('name="code"', (string) $response->getContent());
        $response = $this->handleRequest(uri: '/guestbook/', server: $live);
        self::assertStringNotContainsString('data-live-list-region="guestbook-captcha"', (string) $response->getContent());
        $response = $this->handleRequest(
            uri: '/guestbook/',
            method: 'POST',
            parameters: $this->withCsrfToken(['message' => '<p>Guest message</p>', 'name' => 'Visitor', 'code' => 'wrong']),
            server: $live,
        );
        self::assertSame(200, $response->getStatusCode());
        $document = HTMLDocument::createFromString((string) $response->getContent(), LIBXML_NOERROR);
        self::assertNotNull($document->querySelector('[data-live-list-region="guestbook-captcha"] input[name="code"]'));
        self::assertNotNull($document->querySelector('[data-live-list-region="guestbook-feedback"] .alert'));
        self::assertSame(0, GuestbookEntry::query()->count());
    }
}
