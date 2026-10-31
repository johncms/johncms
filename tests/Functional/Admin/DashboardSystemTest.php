<?php

declare(strict_types=1);

namespace Tests\Functional\Admin;

use Dom\HTMLDocument;
use Johncms\Auth\Authorization\CorePermissions;
use Johncms\Users\User;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\FunctionalUserFactory;

/**
 * The system block of the dashboard is for whoever manages the site: the versions of the server
 * are no business of the rest of the staff, and the cache is cleared by the task of the panel.
 *
 * Whether the scheduler runs depends on the cron of the machine running the suite, so it is tested
 * apart, with the heartbeat doubled.
 */
final class DashboardSystemTest extends FunctionalTestCase
{
    public function testWhoManagesTheSiteSeesTheSystem(): void
    {
        $page = $this->dashboardOf(FunctionalUserFactory::createSupervisor());

        self::assertNotNull($page->querySelector('#admin-system-title'));
        self::assertStringContainsString(CMS_VERSION, (string) $page->querySelector('.admin-system')?->textContent);
        self::assertSame('cache:clear', $page->querySelector('.admin-system form[action="/admin/maintenance/run"] input[name="command"]')?->getAttribute('value'));
    }

    public function testTheRestOfTheStaffDoesNot(): void
    {
        $page = $this->dashboardOf(FunctionalUserFactory::createWithPermissions([CorePermissions::ADMIN_ACCESS]));

        self::assertNull($page->querySelector('#admin-system-title'));
        self::assertNull($page->querySelector('a.admin-attention__item[href="/admin#admin-system-title"]'));
    }

    private function dashboardOf(User $user): HTMLDocument
    {
        $response = $this->handleRequest('/admin', cookies: $this->actingAs($user));

        return HTMLDocument::createFromString((string) $response->getContent(), LIBXML_NOERROR);
    }
}
