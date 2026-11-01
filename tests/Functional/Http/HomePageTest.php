<?php

declare(strict_types=1);

namespace Tests\Functional\Http;

use Dom\HTMLDocument;
use Johncms\Auth\Authorization\RolePermission;
use Tests\Support\FunctionalUserFactory;
use Symfony\Component\HttpFoundation\Response;
use Tests\Functional\FunctionalTestCase;

/**
 * The first page served by Twig, end to end: the controller returns a ViewResponse, the kernel
 * renders it and the chrome around the content comes from the layout of the theme.
 */
final class HomePageTest extends FunctionalTestCase
{
    public function testTheHomePageIsRenderedFromAViewResponse(): void
    {
        $response = $this->handleRequest('/');

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        // A rendered view declares its type; a legacy body deliberately does not, so this is what
        // tells the two paths apart.
        self::assertSame('text/html; charset=UTF-8', $response->headers->get('Content-Type'));
    }

    public function testThePageCarriesTheChromeOfTheLayout(): void
    {
        $content = (string) $this->handleRequest('/')->getContent();

        self::assertStringContainsString('<title>', $content);
        // The sidebar, the main menu and the footer are includes of the layout: a page that
        // rendered without them would still be valid HTML.
        self::assertStringContainsString('site-user', $content);
        self::assertStringContainsString('site-nav__list', $content);
        self::assertStringContainsString('site-footer', $content);
    }

    public function testTheWelcomeUsesSiteSettingsAndEscapesPlainText(): void
    {
        $page = $this->withConfig(['johncms' => [
            'copyright' => 'Our <community>',
            'homepage_welcome' => "Welcome <script>alert(1)</script>\nSecond line",
            'meta_desc' => 'A separate SEO description',
        ]], fn (): HTMLDocument => HTMLDocument::createFromString((string) $this->handleRequest('/')->getContent(), LIBXML_NOERROR));

        self::assertCount(1, $page->querySelectorAll('h1'));
        self::assertSame('Our <community>', $page->querySelector('h1')?->textContent);
        self::assertSame("Welcome <script>alert(1)</script>\nSecond line", $page->querySelector('.site-home__intro')?->textContent);
        self::assertNull($page->querySelector('.site-home__intro script'));
        self::assertSame('A separate SEO description', $page->querySelector('meta[name="description"]')?->getAttribute('content'));
        self::assertNull($page->querySelector('.site-home a[href="https://johncms.com/downloads/"]'));
    }

    public function testAnEmptyWelcomeHasNoPlaceholderParagraph(): void
    {
        $body = $this->withConfig(['johncms' => ['homepage_welcome' => '   ']], fn (): string => (string) $this->handleRequest('/')->getContent());
        $page = HTMLDocument::createFromString($body, LIBXML_NOERROR);

        self::assertNull($page->querySelector('.site-home__intro'));
        self::assertNotNull($page->querySelector('.site-home__actions a[href="/login"]'));
        self::assertNotNull($page->querySelector('.site-home__actions a[href="/registration"]'));
    }

    public function testRegistrationIsNotOfferedWhenGuestsCannotRegister(): void
    {
        RolePermission::query()->where('permission', 'registration.register')->delete();
        $page = HTMLDocument::createFromString((string) $this->handleRequest('/')->getContent(), LIBXML_NOERROR);

        self::assertNull($page->querySelector('.site-home__actions a[href="/registration"]'));
        self::assertNotNull($page->querySelector('.site-home__actions a[href="/login"]'));
    }

    public function testMembersAndAdministratorsSeeTheSameHomeWithoutGuestActions(): void
    {
        foreach ([FunctionalUserFactory::create(), FunctionalUserFactory::createSupervisor()] as $user) {
            $response = $this->handleRequest('/', cookies: $this->actingAs($user));
            $page = HTMLDocument::createFromString((string) $response->getContent(), LIBXML_NOERROR);

            self::assertSame(Response::HTTP_OK, $response->getStatusCode());
            self::assertNotNull($page->querySelector('.site-home__welcome'));
            self::assertNotNull($page->querySelector('.site-home__sections'));
            self::assertNull($page->querySelector('.site-home__actions'));
        }
    }
}
