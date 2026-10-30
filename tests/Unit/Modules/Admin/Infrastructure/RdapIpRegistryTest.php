<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Admin\Infrastructure;

use Johncms\Modules\Admin\Domain\Exceptions\IpRegistryUnavailableException;
use Johncms\Modules\Admin\Infrastructure\Rdap\RdapIpRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Tests\Support\InMemoryCache;

final class RdapIpRegistryTest extends TestCase
{
    private const array BOOTSTRAP = [
        'services' => [
            [['77.0.0.0/8'], ['https://rdap.db.ripe.net/']],
            [['8.0.0.0/8'], ['http://rdap.arin.net/registry/', 'https://rdap.arin.net/registry/']],
            [['8.8.0.0/16'], ['https://rdap.example.net/']],
        ],
    ];

    /** @var list<string> */
    private array $requested = [];

    public function testTheRegistryOfTheLongestPrefixIsAskedOverHttps(): void
    {
        $this->registry(['ip' => new MockResponse('{"handle":"NET-1"}')])->lookup('8.8.8.8');

        self::assertSame('https://rdap.example.net/ip/8.8.8.8', $this->requested[1]);
    }

    public function testTheSummaryIsTakenFromTheAnswerWithTheNestedAbuseContact(): void
    {
        $answer = [
            'handle'       => '77.88.55.0 - 77.88.55.255',
            'name'         => 'YANDEX-77-88-55-0',
            'country'      => 'RU',
            'startAddress' => '77.88.55.0',
            'endAddress'   => '77.88.55.255',
            'events'       => [['eventAction' => 'registration', 'eventDate' => '2012-10-12T13:00:00Z']],
            'entities'     => [[
                'roles'      => ['registrant'],
                'vcardArray' => ['vcard', [['version', [], 'text', '4.0'], ['fn', [], 'text', 'YANDEX LLC']]],
                'entities'   => [[
                    'roles'      => ['abuse'],
                    'vcardArray' => ['vcard', [['email', [], 'text', 'abuse@yandex.ru']]],
                ]],
            ]],
        ];

        $registration = $this->registry(['ip' => new MockResponse((string) json_encode($answer))])->lookup('77.88.55.88');

        self::assertNotNull($registration);
        self::assertSame('77.88.55.0 – 77.88.55.255', $registration->range);
        self::assertSame('', $registration->handle);
        self::assertSame('YANDEX LLC', $registration->organisation);
        self::assertSame('abuse@yandex.ru', $registration->abuseEmail);
        self::assertSame('2012-10-12', $registration->registeredAt);
        self::assertSame('rdap.db.ripe.net', $registration->registry);
    }

    public function testAnAddressTheRegistriesDoNotKnowIsNull(): void
    {
        self::assertNull($this->registry(['ip' => new MockResponse('{}', ['http_code' => 404])])->lookup('77.1.1.1'));
    }

    public function testAFailedRequestIsReportedAsUnavailable(): void
    {
        $this->expectException(IpRegistryUnavailableException::class);

        $this->registry(['ip' => new MockResponse('', ['error' => 'Connection timed out'])])->lookup('77.1.1.1');
    }

    public function testWithoutTheBootstrapFileRdapOrgIsAsked(): void
    {
        $this->registry([
            'bootstrap' => new MockResponse('', ['error' => 'Could not resolve host']),
            'ip'        => new MockResponse('{"handle":"NET-1"}'),
        ])->lookup('8.8.8.8');

        self::assertSame('https://rdap.org/ip/8.8.8.8', $this->requested[1]);
    }

    /**
     * @param array{bootstrap?: MockResponse, ip: MockResponse} $responses
     */
    private function registry(array $responses): RdapIpRegistry
    {
        $bootstrap = $responses['bootstrap'] ?? new MockResponse((string) json_encode(self::BOOTSTRAP));
        $client = new MockHttpClient(function (string $method, string $url) use ($bootstrap, $responses): MockResponse {
            $this->requested[] = $url;

            return str_contains($url, 'data.iana.org') ? $bootstrap : $responses['ip'];
        });

        return new RdapIpRegistry($client, InMemoryCache::create());
    }
}
