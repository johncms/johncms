<?php

declare(strict_types=1);

namespace Tests\Functional\Admin;

use Dom\HTMLDocument;
use Johncms\Auth\Authorization\CorePermissions;
use Johncms\Modules\Downloads\Application\Services\DownloadsPermissions;
use Johncms\Modules\Library\Application\Services\LibraryPermissions;
use Johncms\Users\User;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

/**
 * "Needs attention" on the dashboard of the panel: one line per thing the modules ask the visitor to
 * look at, each checked against what the visitor may do.
 *
 * A line is told by the address it leads to, not by its words: the panel answers in its own
 * language. The accounts are given exact permissions rather than the staff role, so the line of the
 * system check — which depends on how PHP of the machine running the suite is set up — stays out.
 */
final class DashboardAttentionTest extends FunctionalTestCase
{
    public function testAnEmptySiteSaysThatNothingWaits(): void
    {
        $page = $this->dashboardOf(FunctionalUserFactory::createWithPermissions([CorePermissions::ADMIN_ACCESS]));

        self::assertSame([], $this->listed($page));
        self::assertNotNull($page->querySelector('.admin-attention__clear'));
    }

    public function testTheQueuesTheVisitorLooksAfterAreListedWithTheirNumbers(): void
    {
        FunctionalUserFactory::create(['preg' => 0]);
        FunctionalUserFactory::create(['preg' => 0]);
        Fixture::insert('library_texts', ['name' => 'Waiting', 'premod' => 0]);
        Fixture::insert('download__files', ['name' => 'Waiting', 'type' => 3]);

        $page = $this->dashboardOf(FunctionalUserFactory::createWithPermissions([
            CorePermissions::ADMIN_ACCESS,
            DownloadsPermissions::MODERATE,
            LibraryPermissions::MODERATE,
        ]));

        self::assertEqualsCanonicalizing(
            ['/admin/users?status=pending' => '2', '/library/premod' => '1', '/downloads/moderation' => '1'],
            $this->listed($page)
        );
        self::assertNull($page->querySelector('.admin-attention__clear'));
    }

    public function testTheQueueOfAModuleIsNotShownToWhoDoesNotModerateIt(): void
    {
        Fixture::insert('library_texts', ['name' => 'Waiting', 'premod' => 0]);
        Fixture::insert('download__files', ['name' => 'Waiting', 'type' => 3]);

        $page = $this->dashboardOf(FunctionalUserFactory::createWithPermissions([
            CorePermissions::ADMIN_ACCESS,
            LibraryPermissions::MODERATE,
        ]));

        self::assertSame(['/library/premod' => '1'], $this->listed($page));
    }

    private function dashboardOf(User $user): HTMLDocument
    {
        $response = $this->handleRequest('/admin', cookies: $this->actingAs($user));

        return HTMLDocument::createFromString((string) $response->getContent(), LIBXML_NOERROR);
    }

    /**
     * @return array<string, string> The number of each line, by where it leads.
     */
    private function listed(HTMLDocument $page): array
    {
        $lines = [];

        foreach ($page->querySelectorAll('a.admin-attention__item') as $line) {
            $lines[(string) $line->getAttribute('href')] = trim((string) $line->querySelector('.badge')?->textContent);
        }

        return $lines;
    }
}
