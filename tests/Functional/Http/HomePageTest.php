<?php

declare(strict_types=1);

namespace Tests\Functional\Http;

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
        self::assertStringContainsString('sidebar__user', $content);
        self::assertStringContainsString('nav__vertical', $content);
        self::assertStringContainsString('page-footer', $content);
    }
}
