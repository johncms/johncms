<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use Johncms\Http\Request;
use Johncms\Http\RequestPathNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Pins the path normalization that moved out of public/index.php into the kernel (plan stage 3a).
 */
final class RequestPathNormalizerTest extends TestCase
{
    #[DataProvider('paths')]
    public function testPathIsNormalizedForRouteMatching(string $requestUri, string $expected): void
    {
        $normalizer = new RequestPathNormalizer();

        self::assertSame($expected, $normalizer->normalize(Request::create($requestUri)));
    }

    /**
     * A path of nothing but slashes still has to end up as the root route, and Request::create()
     * refuses such a URI, so it is built from the server bag the way the SAPI does it.
     */
    public function testPathOfOnlySlashesBecomesRoot(): void
    {
        $request = new Request([], [], [], [], [], ['REQUEST_URI' => '///']);

        self::assertSame('/', (new RequestPathNormalizer())->normalize($request));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function paths(): array
    {
        return [
            'root stays root'                 => ['/', '/'],
            'trailing slash is dropped'       => ['/forum/', '/forum'],
            'several trailing slashes go too' => ['/forum///', '/forum'],
            'query string is not part of it'  => ['/forum?page=2', '/forum'],
            'percent-encoding is decoded'     => ['/library/%D1%82%D0%B5%D1%81%D1%82', '/library/тест'],
            'encoded path keeps its slashes'  => ['/forum/topic/1/', '/forum/topic/1'],
            'legacy forum entry point'        => ['/forum/index.php', '/forum'],
            'legacy entry point with slash'   => ['/forum/index.php/', '/forum'],
        ];
    }
}
