<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Infrastructure\Rdap;

use Johncms\Cache\CacheInterface;
use Johncms\Modules\Admin\Domain\Exceptions\IpRegistryUnavailableException;
use Johncms\Modules\Admin\Domain\Services\IpRegistryInterface;
use Johncms\Modules\Admin\Domain\ValueObjects\IpRegistration;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

/**
 * Asks the regional registry an address belongs to over RDAP, the JSON successor of whois.
 *
 * Which registry that is comes from the bootstrap files of IANA, kept for a week. When they cannot
 * be had, rdap.org is asked instead: it holds the same table and redirects to the registry.
 * Neither needs an account or a key.
 */
final readonly class RdapIpRegistry implements IpRegistryInterface
{
    private const string BOOTSTRAP_URL = 'https://data.iana.org/rdap/%s.json';
    private const string FALLBACK_URL = 'https://rdap.org/';
    private const string CACHE_KEY = 'admin_rdap_bootstrap_%s';
    private const int CACHE_TTL = 7 * 24 * 3600;

    /** Seconds without a byte, and seconds in all: an admin is waiting on the page. */
    private const int IDLE_TIMEOUT = 5;
    private const int MAX_DURATION = 8;

    public function __construct(
        private HttpClientInterface $httpClient,
        private CacheInterface $cache,
    ) {
    }

    public function lookup(string $ip): ?IpRegistration
    {
        $url = $this->serviceFor($ip) . 'ip/' . $ip;

        try {
            $response = $this->httpClient->request('GET', $url, [
                'headers'      => ['Accept' => 'application/rdap+json, application/json'],
                'timeout'      => self::IDLE_TIMEOUT,
                'max_duration' => self::MAX_DURATION,
            ]);

            if ($response->getStatusCode() === 404) {
                return null;
            }

            $data = $response->toArray();
            $answeredUrl = (string) $response->getInfo('url');
        } catch (ExceptionInterface $exception) {
            throw new IpRegistryUnavailableException($exception->getMessage(), 0, $exception);
        }

        return $this->registration($ip, $data, $answeredUrl);
    }

    /**
     * The base address of the RDAP service of the registry the address is in: the longest prefix
     * of the bootstrap file that holds it.
     */
    private function serviceFor(string $ip): string
    {
        $family = str_contains($ip, ':') ? 'ipv6' : 'ipv4';
        $best = null;
        $bestLength = -1;

        foreach ($this->bootstrap($family) as [$prefixes, $urls]) {
            foreach ($prefixes as $prefix) {
                [$network, $length] = array_pad(explode('/', (string) $prefix, 2), 2, null);
                $length = (int) $length;
                if ($length > $bestLength && $this->inPrefix($ip, (string) $network, $length)) {
                    $best = $this->preferHttps($urls);
                    $bestLength = $length;
                }
            }
        }

        return $best ?? self::FALLBACK_URL;
    }

    /**
     * The services of the bootstrap file: a list of [prefixes, urls]. An empty list when it
     * could not be fetched, which sends the lookup to rdap.org.
     *
     * @return list<array{0: list<string>, 1: list<string>}>
     */
    private function bootstrap(string $family): array
    {
        try {
            return $this->cache->remember(sprintf(self::CACHE_KEY, $family), self::CACHE_TTL, function () use ($family): array {
                $data = $this->httpClient->request('GET', sprintf(self::BOOTSTRAP_URL, $family), [
                    'timeout'      => self::IDLE_TIMEOUT,
                    'max_duration' => self::MAX_DURATION,
                ])->toArray();

                $services = [];
                foreach ($data['services'] ?? [] as $service) {
                    if (is_array($service) && is_array($service[0] ?? null) && is_array($service[1] ?? null)) {
                        $services[] = [array_values(array_map('strval', $service[0])), array_values(array_map('strval', $service[1]))];
                    }
                }

                if ($services === []) {
                    // Not cached empty: the next lookup tries again instead of a week of rdap.org.
                    throw new IpRegistryUnavailableException('The RDAP bootstrap file holds no services.');
                }

                return $services;
            });
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * @param list<string> $urls
     */
    private function preferHttps(array $urls): ?string
    {
        foreach ($urls as $url) {
            if (str_starts_with($url, 'https://')) {
                return rtrim($url, '/') . '/';
            }
        }

        return isset($urls[0]) ? rtrim($urls[0], '/') . '/' : null;
    }

    private function inPrefix(string $ip, string $network, int $length): bool
    {
        $address = @inet_pton($ip);
        $prefix = @inet_pton($network);
        if ($address === false || $prefix === false || strlen($address) !== strlen($prefix)) {
            return false;
        }

        $bytes = intdiv($length, 8);
        $bits = $length % 8;
        if (strncmp($address, $prefix, $bytes) !== 0) {
            return false;
        }
        if ($bits === 0) {
            return true;
        }

        $mask = (0xFF << (8 - $bits)) & 0xFF;

        return (ord($address[$bytes]) & $mask) === (ord($prefix[$bytes]) & $mask);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function registration(string $ip, array $data, string $answeredUrl): IpRegistration
    {
        $start = (string) ($data['startAddress'] ?? '');
        $end = (string) ($data['endAddress'] ?? '');

        $organisation = '';
        $abuseEmail = '';
        foreach ($this->entities($data['entities'] ?? []) as $entity) {
            $roles = (array) ($entity['roles'] ?? []);
            if ($organisation === '' && array_intersect($roles, ['registrant', 'administrative']) !== []) {
                $organisation = $this->vcard($entity, 'fn');
            }
            if ($abuseEmail === '' && in_array('abuse', $roles, true)) {
                $abuseEmail = $this->vcard($entity, 'email');
            }
        }

        $events = [];
        foreach ((array) ($data['events'] ?? []) as $event) {
            if (is_array($event) && isset($event['eventAction'], $event['eventDate'])) {
                $events[(string) $event['eventAction']] = substr((string) $event['eventDate'], 0, 10);
            }
        }

        return new IpRegistration(
            ip: $ip,
            range: $start !== '' && $end !== '' ? $start . ' – ' . $end : '',
            // RIPE hands out the range itself as the handle, which says nothing the range does not.
            handle: str_contains((string) ($data['handle'] ?? ''), ' - ') ? '' : (string) ($data['handle'] ?? ''),
            name: (string) ($data['name'] ?? ''),
            country: (string) ($data['country'] ?? ''),
            organisation: $organisation,
            abuseEmail: $abuseEmail,
            registeredAt: $events['registration'] ?? '',
            updatedAt: $events['last changed'] ?? '',
            registry: (string) parse_url($answeredUrl, PHP_URL_HOST),
            sourceUrl: $answeredUrl,
        );
    }

    /**
     * The entities of an answer with those nested in them: RIPE puts the abuse contact inside the
     * organisation rather than beside it.
     *
     * @return list<array<string, mixed>>
     */
    private function entities(mixed $entities): array
    {
        $flat = [];
        foreach (is_array($entities) ? $entities : [] as $entity) {
            if (! is_array($entity)) {
                continue;
            }
            $flat[] = $entity;
            array_push($flat, ...$this->entities($entity['entities'] ?? []));
        }

        return $flat;
    }

    /**
     * A property of the vCard of an entity (jCard: ["vcard", [[name, params, type, value], …]]).
     *
     * @param array<string, mixed> $entity
     */
    private function vcard(array $entity, string $property): string
    {
        foreach ((array) ($entity['vcardArray'][1] ?? []) as $field) {
            if (is_array($field) && ($field[0] ?? null) === $property && is_string($field[3] ?? null)) {
                return trim($field[3]);
            }
        }

        return '';
    }
}
