<?php

declare(strict_types=1);

namespace Tests\Functional\Admin;

use Dom\HTMLDocument;
use Johncms\Auth\Authorization\CorePermissions;
use Johncms\Users\User;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

/**
 * The security block of the dashboard: the sign-ins refused over the last day by address, the
 * sign-ins of the staff, the bans. What comes from the sign-in log is shown only to whoever may
 * read the log.
 */
final class DashboardSecurityTest extends FunctionalTestCase
{
    private const int DAY = 86400;

    public function testTheAddressesRefusedMostOftenComeFirstAndLeadToTheLog(): void
    {
        $this->refuse('203.0.113.7', 3);
        $this->refuse('198.51.100.23', 1);
        $this->refuse('192.0.2.44', 2);
        // Older than a day: not among the refusals of today.
        Fixture::insert('auth_events', ['event' => 'login.failed', 'ip' => '192.0.2.99', 'created_at' => time() - 2 * self::DAY]);

        $page = $this->dashboardOf(FunctionalUserFactory::createSupervisor());

        self::assertSame(['203.0.113.7', '192.0.2.44', '198.51.100.23'], $this->texts($page, 'a.admin-security__ip'));
        self::assertSame(
            '/admin/auth-log?event=login.failed&period=day&q=203.0.113.7',
            $page->querySelector('a.admin-security__ip')?->getAttribute('href')
        );
        self::assertSame('6', $this->figure($page, '/admin/auth-log?event=login.failed&period=day'));
    }

    public function testTheSignInsOfTheStaffAreListedAndThoseOfMembersAreNot(): void
    {
        $moderator = FunctionalUserFactory::createWithPermissions([], ['name' => 'Gandalf'], level: 60);
        $member = FunctionalUserFactory::create(['name' => 'Pippin']);
        Fixture::insert('auth_events', ['event' => 'login.success', 'user_id' => $moderator->id, 'ip' => '10.0.0.1', 'created_at' => time() - 60]);
        Fixture::insert('auth_events', ['event' => 'login.success', 'user_id' => $member->id, 'ip' => '10.0.0.2', 'created_at' => time() - 30]);

        $page = $this->dashboardOf(FunctionalUserFactory::createSupervisor());

        self::assertContains('Gandalf', $this->texts($page, '.admin-security a.fw-semibold'));
        self::assertNotContains('Pippin', $this->texts($page, '.admin-security a.fw-semibold'));
    }

    public function testTheBansAreCounted(): void
    {
        $now = time();
        Fixture::insert('cms_ban_users', ['user_id' => FunctionalUserFactory::create()->id, 'ban_time' => $now + 3600, 'ban_while' => $now - 60, 'ban_reason' => 'Spam']);
        Fixture::insert('cms_ban_users', ['user_id' => FunctionalUserFactory::create()->id, 'ban_time' => $now + 3600, 'ban_while' => $now - 3 * self::DAY, 'ban_reason' => 'Spam']);
        Fixture::insert('cms_ban_users', ['user_id' => FunctionalUserFactory::create()->id, 'ban_time' => $now - 60, 'ban_while' => $now - 3 * self::DAY, 'ban_reason' => 'Spam']);

        $page = $this->dashboardOf(FunctionalUserFactory::createSupervisor());

        self::assertSame('2', $this->figure($page, '/admin/bans'));
        self::assertSame('1', trim((string) $page->querySelector('.admin-security div.admin-stat .admin-stat__value')?->textContent), 'Issued today.');
    }

    public function testAnAddressRefusedOverAndOverNeedsAttention(): void
    {
        $this->refuse('203.0.113.7', 10);
        $this->refuse('198.51.100.23', 9);

        $page = $this->dashboardOf(FunctionalUserFactory::createSupervisor());
        $line = $page->querySelector('a.admin-attention__item[href="/admin/auth-log?event=login.failed&period=day"]');

        self::assertNotNull($line);
        self::assertSame('1', trim((string) $line->querySelector('.badge')?->textContent), 'Only the address refused ten times.');
    }

    public function testAFewSlipsNeedNoAttention(): void
    {
        $this->refuse('203.0.113.7', 9);

        $page = $this->dashboardOf(FunctionalUserFactory::createSupervisor());

        self::assertNull($page->querySelector('a.admin-attention__item[href^="/admin/auth-log"]'));
    }

    public function testWhoMayNotReadTheLogSeesTheBansAlone(): void
    {
        $this->refuse('203.0.113.7', 10);

        $page = $this->dashboardOf(FunctionalUserFactory::createWithPermissions([CorePermissions::ADMIN_ACCESS]));

        self::assertNotNull($page->querySelector('.admin-security a[href="/admin/bans"]'));
        self::assertNull($page->querySelector('a[href^="/admin/auth-log"]'), 'Neither in the block nor among the lines that need attention.');
        self::assertSame([], $this->texts($page, 'a.admin-security__ip'));
    }

    private function refuse(string $ip, int $times): void
    {
        for ($attempt = 0; $attempt < $times; $attempt++) {
            Fixture::insert('auth_events', ['event' => 'login.failed', 'ip' => $ip, 'created_at' => time() - 60 * ($attempt + 1)]);
        }
    }

    private function dashboardOf(User $user): HTMLDocument
    {
        $response = $this->handleRequest('/admin', cookies: $this->actingAs($user));

        return HTMLDocument::createFromString((string) $response->getContent(), LIBXML_NOERROR);
    }

    private function figure(HTMLDocument $page, string $url): ?string
    {
        $value = $page->querySelector('.admin-security a.admin-stat[href="' . $url . '"] .admin-stat__value')?->textContent;

        return $value === null ? null : trim($value);
    }

    /**
     * @return list<string>
     */
    private function texts(HTMLDocument $page, string $selector): array
    {
        $texts = [];

        foreach ($page->querySelectorAll($selector) as $element) {
            $texts[] = trim((string) $element->textContent);
        }

        return $texts;
    }
}
