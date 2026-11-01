<?php

declare(strict_types=1);

namespace Tests\Functional\Http;

use Dom\HTMLDocument;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\FunctionalUserFactory;

final class PublicShellTest extends FunctionalTestCase
{
    public function testGuestNavigationHasWorkingAuthenticationLinksAndNoStaffMenu(): void
    {
        $page = $this->page('/forum/');

        self::assertNotNull($page->querySelector('.site-sidebar a[href="/login/"]'));
        self::assertNotNull($page->querySelector('.site-sidebar a[href="/registration/"]'));
        self::assertNull($page->querySelector('[data-site-menu="staff"]'));
        self::assertNull($page->querySelector('a[href="/logout"]'));
        self::assertSame('#site-sidebar', $page->querySelector('[data-site-nav-open]')?->getAttribute('href'));
        self::assertNotNull($page->querySelector('main#site-content'));
        self::assertCount(1, $page->querySelectorAll('.site-topbar a[href="/"]'));
    }

    public function testMemberKeepsPersonalActionsAndStaffPermissionsAreRespected(): void
    {
        $user = FunctionalUserFactory::create(['name' => 'A <member>']);
        $response = $this->handleRequest('/forum/', cookies: $this->actingAs($user));
        $page = HTMLDocument::createFromString((string) $response->getContent(), LIBXML_NOERROR);

        self::assertSame('A <member>', $page->querySelector('.site-user__name')?->textContent);
        self::assertNotNull($page->querySelector('.site-user a[href="/profile/account"]'));
        self::assertNotNull($page->querySelector('.site-user a[href="/notifications/"]'));
        self::assertNotNull($page->querySelector('.site-user a[href="/logout"]'));
        self::assertNull($page->querySelector('[data-site-menu="staff"]'));
        self::assertSame('A <member>', $page->querySelector('[data-site-account] .site-popover__header')?->textContent);
        self::assertNotNull($page->querySelector('[data-site-account] a[href="/profile/' . $user->id . '"]'));
        self::assertNotNull($page->querySelector('[data-site-account] a[href="/notifications/"]'));
        self::assertNotNull($page->querySelector('[data-site-account] a[href="/logout"]'));
        self::assertNull($page->querySelector('[data-site-account] a[href="/"]'));

        $response = $this->handleRequest('/forum/', cookies: $this->actingAs(FunctionalUserFactory::createSupervisor()));
        $page = HTMLDocument::createFromString((string) $response->getContent(), LIBXML_NOERROR);

        self::assertNotNull($page->querySelector('[data-site-menu="staff"] a[href="/admin/"]'));
    }

    public function testPagesKeepOneHeadingAndOneBreadcrumbChain(): void
    {
        foreach (['/news/', '/forum/', '/guestbook/', '/login/'] as $url) {
            $page = $this->page($url);

            self::assertCount(1, $page->querySelectorAll('h1'), $url);
            self::assertCount(1, $page->querySelectorAll('[typeof="BreadcrumbList"]'), $url);
        }
    }

    public function testBrandingAndLegalLinksComeFromTheSiteSettings(): void
    {
        $this->withConfig(['johncms' => [
            'copyright' => 'Local <site>',
            'privacy_policy_url' => '/privacy',
            'terms_of_use_url' => '/terms',
        ]], function (): void {
            $page = $this->page('/');

            self::assertSame('Local <site>', $page->querySelector('.site-brand__name')?->textContent);
            self::assertNotNull($page->querySelector('.site-footer a[href="/privacy"]'));
            self::assertNotNull($page->querySelector('.site-footer a[href="/terms"]'));
            self::assertNull($page->querySelector('.site-footer a[href="https://twitter.com/johncms"]'));
        });
    }

    public function testPreferencesKeepTheServerLanguageAndThemeContracts(): void
    {
        $this->withConfig(['johncms' => ['lng' => 'en', 'lng_list' => [
            'en' => ['name' => 'English', 'version' => 1.0],
            'ru' => ['name' => 'Русский', 'version' => 1.0],
            'zz' => ['name' => 'Custom language', 'version' => 1.0],
        ]]], function (): void {
            $response = $this->handleRequest('/forum/', cookies: ['siteTheme' => 'dark']);
            $page = HTMLDocument::createFromString((string) $response->getContent(), LIBXML_NOERROR);

            self::assertSame('dark', $page->querySelector('[data-site-theme-picker]')?->getAttribute('data-current-theme'));
            self::assertSame('true', $page->querySelector('[data-site-theme="dark"]')?->getAttribute('aria-pressed'));
            self::assertNotNull($page->querySelector('[data-site-theme="auto"]'));
            self::assertSame('?setlng=ru', $page->querySelector('[data-site-language="ru"]')?->getAttribute('href'));
            self::assertStringEndsWith('/images/flags/ru.svg', $page->querySelector('[data-site-language="ru"] .site-flag')?->getAttribute('src'));
            self::assertStringEndsWith('/images/flags/en.svg', $page->querySelector('[data-site-language="en"] .site-flag')?->getAttribute('src'));
            self::assertNotNull($page->querySelector('.site-topbar summary .site-flag'));
            self::assertNotNull($page->querySelector('[data-site-language="zz"] svg'));
            self::assertNull($page->querySelector('[data-site-language="zz"] img'));
        });
    }

    public function testBrowserThemeColorIsCorrectBeforeJavaScriptRuns(): void
    {
        foreach (['light', 'dark', 'auto'] as $scheme) {
            $response = $this->handleRequest('/forum/', cookies: ['siteTheme' => $scheme]);
            $page = HTMLDocument::createFromString((string) $response->getContent(), LIBXML_NOERROR);
            $metas = $page->querySelectorAll('meta[name="theme-color"]');
            $expected = $scheme === 'auto'
                ? [['#fafaf7', '(prefers-color-scheme: light)'], ['#0e1218', '(prefers-color-scheme: dark)']]
                : [[$scheme === 'dark' ? '#0e1218' : '#fafaf7', null]];

            self::assertCount(count($expected), $metas);
            foreach ($metas as $index => $meta) {
                self::assertSame($expected[$index][0], $meta->getAttribute('content'));
                self::assertSame($expected[$index][1], $meta->getAttribute('media'));
            }
        }
    }

    private function page(string $url): HTMLDocument
    {
        $response = $this->handleRequest($url, catch: false);
        self::assertSame(200, $response->getStatusCode());

        return HTMLDocument::createFromString((string) $response->getContent(), LIBXML_NOERROR);
    }
}
