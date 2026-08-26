<?php

declare(strict_types=1);

namespace Tests\Functional\Admin;

use Johncms\Auth\Authorization\CorePermissions;
use Johncms\NavChain;
use Johncms\Modules\News\Application\Services\NewsPermissions;
use Symfony\Component\HttpFoundation\Response;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\FunctionalUserFactory;

/**
 * The panel is written in its own translations, and a section belonging to a module is still the
 * panel. It used not to be: the domain was entered by the access guards of the admin module, so
 * the news section — guarded by a permission of its own — rendered the whole menu in the source
 * English.
 */
final class AdminAreaContextTest extends FunctionalTestCase
{
    public function testASectionOfAModuleGetsTheTranslationsOfThePanel(): void
    {
        $editor = FunctionalUserFactory::createWithPermissions([
            CorePermissions::ADMIN_ACCESS,
            NewsPermissions::MANAGE,
        ]);

        $response = $this->handleRequest('/admin/news', cookies: $this->actingAs($editor));

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $body = (string) $response->getContent();

        // A string of the panel domain: the menu of the panel, drawn on every page of it.
        self::assertStringContainsString('Бан панель', $body);
        self::assertStringContainsString('Админ панель', $body, 'The navigation chain of the panel.');
    }

    /**
     * The same page also gets the first link of the navigation chain, which the panel context adds.
     */
    public function testASectionOfAModuleGetsTheNavigationChainOfThePanel(): void
    {
        $editor = FunctionalUserFactory::createWithPermissions([
            CorePermissions::ADMIN_ACCESS,
            NewsPermissions::MANAGE,
        ]);

        $response = $this->handleRequest('/admin/news', cookies: $this->actingAs($editor));

        self::assertStringContainsString('Админ панель', (string) $response->getContent());
    }

    /**
     * Entering the context twice would repeat that first link — which is what would happen if the
     * guards of the panel kept entering it now that the pipeline does.
     */
    public function testTheNavigationChainOfThePanelIsNotRepeated(): void
    {
        $admin = FunctionalUserFactory::createWithPermissions([CorePermissions::ADMIN_ACCESS]);

        $this->handleRequest('/admin', cookies: $this->actingAs($admin));

        /** @var NavChain $navChain */
        $navChain = $this->container()->get(NavChain::class);
        $links = array_filter(
            $navChain->getAll(),
            static fn (array $link): bool => ($link['url'] ?? '') === '/admin/'
        );

        self::assertCount(1, $links);
    }
}
