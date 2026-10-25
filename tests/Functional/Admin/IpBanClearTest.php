<?php

declare(strict_types=1);

namespace Tests\Functional\Admin;

use Illuminate\Database\Capsule\Manager as Capsule;
use Symfony\Component\HttpFoundation\Response;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

/**
 * Lifting every ban by an address at once.
 */
final class IpBanClearTest extends FunctionalTestCase
{
    public function testEveryBanByAnAddressIsLifted(): void
    {
        Fixture::insert('cms_ban_ip', ['ip1' => ip2long('10.0.0.1'), 'ip2' => ip2long('10.0.0.1'), 'ban_type' => 1]);
        Fixture::insert('cms_ban_ip', ['ip1' => ip2long('10.0.1.0'), 'ip2' => ip2long('10.0.1.255'), 'ban_type' => 1]);

        $response = $this->postForm('/admin/ip-bans/clear', [], FunctionalUserFactory::createSupervisor());

        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        self::assertSame('/admin/ip-bans', $response->headers->get('Location'));
        self::assertSame(0, Capsule::table('cms_ban_ip')->count());
    }
}
