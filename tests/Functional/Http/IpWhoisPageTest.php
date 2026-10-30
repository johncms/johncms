<?php

declare(strict_types=1);

namespace Tests\Functional\Http;

use Symfony\Component\HttpFoundation\Response;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\FunctionalUserFactory;

/**
 * The states that are told without asking a registry: those that do are covered by the test of
 * the RDAP adapter, with no network behind it.
 */
final class IpWhoisPageTest extends FunctionalTestCase
{
    public function testWhatIsNotAnAddressIsSaidSo(): void
    {
        $content = $this->page('not-an-ip');

        self::assertStringContainsString('alert-danger', $content);
        self::assertStringContainsString('value="not-an-ip"', $content);
    }

    public function testALocalAddressIsNotLookedUp(): void
    {
        self::assertStringContainsString('alert-info', $this->page('192.168.1.10'));
    }

    public function testThePageIsInTheUsersSection(): void
    {
        self::assertMatchesRegularExpression('#href="/admin/ip-whois"[^>]*aria-current="page"|aria-current="page"[^>]*href="/admin/ip-whois"#', $this->page(''));
    }

    private function page(string $ip): string
    {
        $response = $this->handleRequest('/admin/ip-whois?ip=' . rawurlencode($ip), cookies: $this->actingAs(FunctionalUserFactory::createSupervisor()));
        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        return (string) $response->getContent();
    }
}
