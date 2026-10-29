<?php

declare(strict_types=1);

namespace Tests\Functional\Admin;

use Johncms\Modules\Admin\Domain\Models\Ad;
use Symfony\Component\HttpFoundation\Response;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

/**
 * The advertising links in the panel: where they are placed, what each form choice means, and
 * what clearing inactive links asks and removes.
 */
final class AdsAdminTest extends FunctionalTestCase
{
    public function testTheListShowsPlacementTabsAndThePageLayoutSeparately(): void
    {
        $this->ad([
            'name'   => 'Placement and layout demo',
            'type'   => 1,
            'layout' => 2,
        ]);

        $response = $this->handleRequest(
            '/admin/ads?type=1',
            cookies: $this->actingAs(FunctionalUserFactory::createSupervisor())
        );
        $content = (string) $response->getContent();

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringContainsString('href="/admin/ads?type=1" aria-current="page"', $content);
        self::assertStringContainsString('Placement and layout demo', $content);
        self::assertStringContainsString('После меню', $content);
        self::assertStringContainsString('На всех, кроме главной страницы', $content);
    }

    public function testTheFormExplainsItsRadioChoicesAndShowsSectionIcons(): void
    {
        $response = $this->handleRequest(
            '/admin/ads/new',
            cookies: $this->actingAs(FunctionalUserFactory::createSupervisor())
        );
        $content = (string) $response->getContent();

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringContainsString('Показывать ссылку только незарегистрированным посетителям.', $content);
        self::assertStringContainsString('Над меню сайта.', $content);
        self::assertStringContainsString('Показывать на всех страницах, кроме главной.', $content);
        foreach (['#link', '#users', '#pin', '#layout-grid', '#palette'] as $icon) {
            self::assertStringContainsString($icon, $content);
        }
    }

    public function testClearingInactiveLinksUsesTheDialogForModalAndPlainVisits(): void
    {
        $cookies = $this->actingAs(FunctionalUserFactory::createSupervisor());
        $dialog = $this->handleRequest(
            '/admin/ads/clear',
            server: ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'],
            cookies: $cookies
        );
        $page = $this->handleRequest('/admin/ads/clear', cookies: $cookies);

        self::assertSame(Response::HTTP_OK, $dialog->getStatusCode());
        self::assertStringStartsWith('<form class="modal-content" method="post" action="/admin/ads/clear">', trim((string) $dialog->getContent()));
        self::assertStringContainsString('Вы действительно хотите удалить все неактивные ссылки?', (string) $dialog->getContent());
        self::assertSame(Response::HTTP_OK, $page->getStatusCode());
        self::assertStringContainsString('class="modal admin-dialog-page"', (string) $page->getContent());
    }

    public function testClearingInactiveLinksKeepsActiveLinksAndShowsSuccess(): void
    {
        $inactiveOne = $this->ad(['to' => 1]);
        $inactiveTwo = $this->ad(['to' => 1, 'name' => 'Another inactive link']);
        $active = $this->ad(['to' => 0, 'name' => 'Active link']);
        $cookies = $this->actingAs(FunctionalUserFactory::createSupervisor());

        $response = $this->handleRequest(
            '/admin/ads/clear',
            'POST',
            $this->withCsrfToken(),
            cookies: $cookies
        );
        $list = $this->handleRequest('/admin/ads', cookies: $cookies);

        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        self::assertSame('/admin/ads', $response->headers->get('Location'));
        self::assertNull(Ad::query()->find($inactiveOne));
        self::assertNull(Ad::query()->find($inactiveTwo));
        self::assertNotNull(Ad::query()->find($active));
        self::assertStringContainsString('Неактивные ссылки удалены', (string) $list->getContent());
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function ad(array $attributes = []): int
    {
        return Fixture::insert('cms_ads', $attributes + [
            'type'       => 0,
            'view'       => 0,
            'layout'     => 0,
            'count'      => 0,
            'count_link' => 0,
            'name'       => 'Test advertising link',
            'link'       => 'https://example.com/advertisement',
            'to'         => 0,
            'color'      => '',
            'time'       => time(),
            'day'        => 0,
            'mesto'      => 1,
            'bold'       => 0,
            'italic'     => 0,
            'underline'  => 0,
            'show'       => 0,
        ]);
    }
}
