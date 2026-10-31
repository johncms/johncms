<?php

declare(strict_types=1);

namespace Tests\Functional\Admin;

use Dom\HTMLDocument;
use Johncms\Auth\Authorization\CorePermissions;
use Johncms\Modules\News\Application\Services\NewsPermissions;
use Johncms\Users\User;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\FunctionalUserFactory;

/**
 * The quick actions at the top of the dashboard come from the modules, and each is shown only to
 * whoever may take it. An action is told by where it leads.
 */
final class DashboardActionsTest extends FunctionalTestCase
{
    public function testWhoManagesTheSiteHasEveryAction(): void
    {
        self::assertSame(
            ['/admin/users', '/admin/ip-search', '/admin/news/articles/new', '/admin/ads/new', '/admin/settings'],
            $this->actionsOf(FunctionalUserFactory::createSupervisor())
        );
    }

    public function testAModeratorHasTheActionsOfThePanelAlone(): void
    {
        self::assertSame(
            ['/admin/users', '/admin/ip-search', '/admin/ads/new'],
            $this->actionsOf(FunctionalUserFactory::createWithPermissions([CorePermissions::ADMIN_ACCESS]))
        );
    }

    public function testTheEditorOfTheNewsMayWriteThem(): void
    {
        self::assertContains(
            '/admin/news/articles/new',
            $this->actionsOf(FunctionalUserFactory::createWithPermissions([CorePermissions::ADMIN_ACCESS, NewsPermissions::MANAGE]))
        );
    }

    /**
     * @return list<string>
     */
    private function actionsOf(User $user): array
    {
        $response = $this->handleRequest('/admin', cookies: $this->actingAs($user));
        $page = HTMLDocument::createFromString((string) $response->getContent(), LIBXML_NOERROR);

        $urls = [];
        foreach ($page->querySelectorAll('.admin-actions a') as $link) {
            $urls[] = (string) $link->getAttribute('href');
        }

        return $urls;
    }
}
