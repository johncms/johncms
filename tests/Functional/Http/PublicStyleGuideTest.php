<?php

declare(strict_types=1);

namespace Tests\Functional\Http;

use Dom\HTMLDocument;
use Johncms\Auth\Authorization\CorePermissions;
use Symfony\Component\HttpFoundation\Response;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\FunctionalUserFactory;

final class PublicStyleGuideTest extends FunctionalTestCase
{
    public function testGuestMustSignIn(): void
    {
        $response = $this->handleRequest('/style-guide');

        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        self::assertSame('/login', $response->headers->get('Location'));
    }

    public function testMemberWithoutAdminAccessIsRefused(): void
    {
        $cookies = $this->actingAs(FunctionalUserFactory::create());
        $response = $this->handleRequest('/style-guide', cookies: $cookies);

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());

        $response = $this->handleRequest('/forum/', cookies: $cookies);
        $page = HTMLDocument::createFromString((string) $response->getContent(), LIBXML_NOERROR);
        self::assertNull($page->querySelector('[data-site-account] a[href="/style-guide"]'));
    }

    public function testAdminAccessAloneOpensThePublicGuideWithEscapedSource(): void
    {
        $user = FunctionalUserFactory::createWithPermissions([CorePermissions::ADMIN_ACCESS]);
        $response = $this->handleRequest('/style-guide', cookies: $this->actingAs($user));

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        $body = (string) $response->getContent();
        $page = HTMLDocument::createFromString($body, LIBXML_NOERROR);

        self::assertNotNull($page->querySelector('.site-shell'));
        self::assertNull($page->querySelector('.admin-shell'));
        self::assertNotNull($page->querySelector('[data-site-account] a[href="/style-guide"]'));
        self::assertCount(1, $page->querySelectorAll('h1'));
        self::assertNotNull($page->querySelector('#typography'));
        self::assertNotNull($page->querySelector('#buttons .btn-primary[disabled]'));
        self::assertSame('noindex,follow', $page->querySelector('meta[name="robots"]')?->getAttribute('content'));
        self::assertCount(15, $page->querySelectorAll('.site-guide__code'));
        self::assertCount(4, $page->querySelectorAll('#messages [data-toast-show]'));
        self::assertNotNull($page->querySelector('[data-toasts][aria-live="polite"]'), 'The stack of toasts is on every public page from the start.');
        self::assertNotNull($page->querySelector('#dropdowns details[data-site-popover] .site-popover__panel'));
        self::assertNotNull($page->querySelector('#colors [data-site-palette] [data-contrast-pair]'));
        self::assertNotNull($page->querySelector('#pagination .pagination .page-item.active'));
        self::assertNull($page->querySelector('.site-guide__code button'));
        self::assertStringContainsString('&lt;button type=&quot;button&quot; class=&quot;btn btn-primary&quot;&gt;btn-primary&lt;/button&gt;', $body);
        self::assertStringContainsString('{% include &#039;@theme/components/icon.twig&#039;', $body);
        self::assertStringNotContainsString('/build/assets/admin-', $body);
    }
}
