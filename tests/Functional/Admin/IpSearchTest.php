<?php

declare(strict_types=1);

namespace Tests\Functional\Admin;

use Johncms\Auth\Authorization\CorePermissions;
use Johncms\Users\User;
use Symfony\Component\HttpFoundation\Response;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

/**
 * Looking accounts up by an address: the one they have now, or any they have come from.
 */
final class IpSearchTest extends FunctionalTestCase
{
    public function testAnAddressFindsTheAccountsThatHaveItNow(): void
    {
        FunctionalUserFactory::create(['name' => 'Bombadil', 'ip' => ip2long('198.51.100.7')]);
        FunctionalUserFactory::create(['name' => 'Goldberry', 'ip' => ip2long('198.51.100.8')]);

        $page = $this->page('/admin/ip-search?ip=198.51.100.7');

        self::assertStringContainsString('Bombadil', $page);
        self::assertStringNotContainsString('Goldberry', $page);
    }

    public function testARangeAndAMaskFindEveryAddressInThem(): void
    {
        FunctionalUserFactory::create(['name' => 'Bombadil', 'ip' => ip2long('198.51.100.7')]);
        FunctionalUserFactory::create(['name' => 'Goldberry', 'ip' => ip2long('198.51.100.200')]);
        FunctionalUserFactory::create(['name' => 'Maggot', 'ip' => ip2long('203.0.113.5')]);

        $range = $this->page('/admin/ip-search?search=' . rawurlencode('198.51.100.1-198.51.100.10'));
        $mask = $this->page('/admin/ip-search?search=' . rawurlencode('198.51.100.*'));

        self::assertStringContainsString('Bombadil', $range);
        self::assertStringNotContainsString('Goldberry', $range);
        self::assertStringContainsString('Goldberry', $mask);
        self::assertStringNotContainsString('Maggot', $mask);
    }

    /**
     * An address typed half way is what the live list sends between two keys, so it is said
     * quietly in place of the results rather than as an error.
     */
    public function testASearchThatIsNoAddressSaysSoInPlaceOfTheResults(): void
    {
        $page = $this->page('/admin/ip-search?search=' . rawurlencode('198.51.'));

        self::assertStringContainsString('admin-empty', $page);
        self::assertStringNotContainsString('alert-danger', $page);
        self::assertStringNotContainsString('<table', $page);
    }

    public function testTheLiveListIsAnsweredWithItsRegionsAlone(): void
    {
        FunctionalUserFactory::create(['name' => 'Bombadil', 'ip' => ip2long('198.51.100.7')]);

        $response = $this->handleRequest(
            '/admin/ip-search?search=198.51.100.7',
            server: ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'],
            cookies: $this->actingAs(FunctionalUserFactory::createSupervisor())
        );
        $body = (string) $response->getContent();

        self::assertStringContainsString('data-live-list-region="results"', $body);
        self::assertStringContainsString('data-live-list-region="tabs"', $body);
        self::assertStringContainsString('Bombadil', $body);
        self::assertStringNotContainsString('<html', $body);
    }

    /**
     * An address used once and left since is what the history is for: the account is found by a
     * record older than its latest one, and once however many records match.
     */
    public function testTheHistoryFindsAnAddressTheAccountHasMovedOnFrom(): void
    {
        $member = FunctionalUserFactory::create(['name' => 'Fatty', 'ip' => ip2long('203.0.113.9')]);
        $this->cameFrom($member, '198.51.100.7', time() - 7200);
        $this->cameFrom($member, '198.51.100.7', time() - 5400);
        $this->cameFrom($member, '192.0.2.44', time() - 3600);

        $page = $this->page('/admin/ip-search/history?search=198.51.100.7');

        self::assertSame(1, substr_count($page, '>Fatty</a>'));
        self::assertStringContainsString('>198.51.100.7</a>', $page, 'The row shows the address of the history.');
    }

    public function testTheAddressesAreForWhoeverMayReadThem(): void
    {
        FunctionalUserFactory::create(['name' => 'Bombadil', 'ip' => ip2long('198.51.100.7'), 'browser' => 'Lynx/2.9']);
        $staff = FunctionalUserFactory::createWithPermissions([CorePermissions::ADMIN_ACCESS], level: 20);

        self::assertStringContainsString('Lynx/2.9', $this->page('/admin/ip-search?ip=198.51.100.7'));
        self::assertStringNotContainsString('Lynx/2.9', $this->page('/admin/ip-search?ip=198.51.100.7', $staff));
    }

    public function testThePageWithoutASearchSaysWhatToType(): void
    {
        $page = $this->page('/admin/ip-search');

        self::assertStringContainsString('admin-empty', $page);
        self::assertStringNotContainsString('<table', $page);
    }

    private function cameFrom(User $user, string $ip, int $time): void
    {
        Fixture::insert('cms_users_iphistory', ['user_id' => $user->id, 'ip' => ip2long($ip), 'ip_via_proxy' => 0, 'time' => $time]);
    }

    /**
     * The content of the page alone. It is no live list with a fragment to ask for, and the page
     * around it carries the query log when debugging is on, where a name left out of the results
     * would be found all the same.
     */
    private function page(string $uri, ?User $viewer = null): string
    {
        $response = $this->handleRequest($uri, cookies: $this->actingAs($viewer ?? FunctionalUserFactory::createSupervisor()));
        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $body = (string) $response->getContent();
        $start = strpos($body, '<main');
        $end = strpos($body, '</main>');
        self::assertNotFalse($start);
        self::assertNotFalse($end);

        return substr($body, $start, $end - $start);
    }
}
