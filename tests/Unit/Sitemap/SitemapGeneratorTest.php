<?php

declare(strict_types=1);

namespace Tests\Unit\Sitemap;

use Closure;
use Johncms\Config\ConfigRepository;
use Johncms\Sitemap\CoreUrlsProvider;
use Johncms\Sitemap\RobotsTxtUpdater;
use Johncms\Sitemap\SitemapGenerator;
use Johncms\Sitemap\SitemapUrlEntry;
use Johncms\Sitemap\SitemapUrlProviderInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use SimpleXMLElement;

/**
 * The sitemap a search engine reads: a file per group of URLs, cut into chunks below the 50 000
 * URLs the protocol allows in one file, an index listing the chunks, and robots.txt pointing at it.
 */
final class SitemapGeneratorTest extends TestCase
{
    private string $root;

    private string $publicPath;

    private string $cachePath;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . DS . 'johncms-sitemap-' . uniqid() . DS;
        $this->publicPath = $this->root . 'public' . DS;
        $this->cachePath = $this->root . 'cache' . DS;
        mkdir($this->publicPath, 0o777, true);
        mkdir($this->cachePath, 0o777, true);

        ConfigRepository::init(['johncms' => ['homeurl' => 'https://example.com']]);
    }

    protected function tearDown(): void
    {
        exec('rm -rf ' . escapeshellarg($this->root));
    }

    public function testTheSectionsOfTheCoreGoToAFileOfTheirOwn(): void
    {
        $this->generator()->generate();

        self::assertSame(
            [
                'https://example.com/',
                'https://example.com/news/',
                'https://example.com/forum/',
                'https://example.com/community/',
                'https://example.com/guestbook/',
                'https://example.com/downloads/',
                'https://example.com/library/',
                'https://example.com/help/',
                'https://example.com/album',
                'https://example.com/online/',
            ],
            $this->locations('sitemap-core-1.xml')
        );
    }

    public function testTheIndexListsTheChunkOfEveryGroupUnderTheAddressOfTheSite(): void
    {
        $this->generator(
            $this->provider('blog', static fn (string $homeUrl): array => [new SitemapUrlEntry($homeUrl . '/blog/')]),
        )->generate();

        self::assertSame(
            implode(PHP_EOL, [
                '<?xml version="1.0" encoding="UTF-8"?>',
                '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
                '  <sitemap>',
                '    <loc>https://example.com/sitemap-core-1.xml</loc>',
                '  </sitemap>',
                '  <sitemap>',
                '    <loc>https://example.com/sitemap-blog-1.xml</loc>',
                '  </sitemap>',
                '</sitemapindex>',
            ]) . PHP_EOL,
            file_get_contents($this->publicPath . 'sitemap.xml')
        );
    }

    public function testTheDateOfALastChangeIsWrittenOnlyWhenTheProviderKnowsIt(): void
    {
        $this->generator(
            $this->provider('blog', static fn (string $homeUrl): array => [
                new SitemapUrlEntry($homeUrl . '/blog/first', '2026-10-24T03:00:00+00:00'),
                new SitemapUrlEntry($homeUrl . '/blog/'),
            ]),
        )->generate();

        self::assertSame(
            implode(PHP_EOL, [
                '<?xml version="1.0" encoding="UTF-8"?>',
                '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
                '  <url>',
                '    <loc>https://example.com/blog/first</loc>',
                '    <lastmod>2026-10-24T03:00:00+00:00</lastmod>',
                '  </url>',
                '  <url>',
                '    <loc>https://example.com/blog/</loc>',
                '  </url>',
                '</urlset>',
            ]) . PHP_EOL,
            file_get_contents($this->publicPath . 'sitemap-blog-1.xml')
        );
    }

    public function testTheAddressOfTheSiteIsGivenWithoutSpacesAndATrailingSlash(): void
    {
        ConfigRepository::init(['johncms' => ['homeurl' => ' https://example.com/ ']]);
        $given = null;

        $this->generator(
            $this->provider('blog', static function (string $homeUrl) use (&$given): array {
                $given = $homeUrl;

                return [];
            }),
        )->generate();

        self::assertSame('https://example.com', $given);
        self::assertSame('https://example.com/', $this->locations('sitemap-core-1.xml')[0]);
        self::assertSame(['https://example.com/sitemap-core-1.xml'], $this->locations('sitemap.xml'));
    }

    public function testTheCharactersXmlReservesAreEscapedInAnAddress(): void
    {
        $address = 'https://example.com/search?tag=<b>&q="news"';

        $this->generator(
            $this->provider('blog', static fn (): array => [new SitemapUrlEntry($address)]),
        )->generate();

        self::assertStringContainsString(
            '<loc>https://example.com/search?tag=&lt;b&gt;&amp;q=&quot;news&quot;</loc>',
            (string) file_get_contents($this->publicPath . 'sitemap-blog-1.xml')
        );
        self::assertSame([$address], $this->locations('sitemap-blog-1.xml'));
    }

    public function testAGroupOverTheLimitIsCutIntoChunks(): void
    {
        $this->generator(
            $this->provider('blog', static fn (string $homeUrl): iterable => self::entries($homeUrl, 45001)),
        )->generate();

        $first = $this->locations('sitemap-blog-1.xml');
        self::assertCount(45000, $first);
        self::assertSame('https://example.com/page/45000', end($first));
        self::assertSame(['https://example.com/page/45001'], $this->locations('sitemap-blog-2.xml'));
        self::assertSame(
            [
                'https://example.com/sitemap-core-1.xml',
                'https://example.com/sitemap-blog-1.xml',
                'https://example.com/sitemap-blog-2.xml',
            ],
            $this->locations('sitemap.xml')
        );
    }

    public function testAGroupOfExactlyTheLimitFitsIntoOneChunk(): void
    {
        $this->generator(
            $this->provider('blog', static fn (string $homeUrl): iterable => self::entries($homeUrl, 45000)),
        )->generate();

        self::assertCount(45000, $this->locations('sitemap-blog-1.xml'));
        self::assertFileDoesNotExist($this->publicPath . 'sitemap-blog-2.xml');
    }

    public function testAGroupWithoutUrlsGetsNoChunk(): void
    {
        $this->generator($this->provider('blog', static fn (): array => []))->generate();

        self::assertFileDoesNotExist($this->publicPath . 'sitemap-blog-1.xml');
        self::assertSame(['https://example.com/sitemap-core-1.xml'], $this->locations('sitemap.xml'));
    }

    /**
     * A group that shrank, or whose module is gone, would otherwise leave its chunks behind for a
     * search engine to find. Nothing but the chunks is touched.
     */
    public function testChunksTheGenerationNoLongerWritesAreRemoved(): void
    {
        file_put_contents($this->publicPath . 'sitemap-blog-2.xml', 'stale');
        file_put_contents($this->publicPath . 'sitemap-gone-1.xml', 'stale');
        file_put_contents($this->publicPath . 'favicon.xml', 'kept');

        $this->generator(
            $this->provider('blog', static fn (string $homeUrl): array => [new SitemapUrlEntry($homeUrl . '/blog/')]),
        )->generate();

        self::assertFileExists($this->publicPath . 'sitemap-blog-1.xml');
        self::assertFileDoesNotExist($this->publicPath . 'sitemap-blog-2.xml');
        self::assertFileDoesNotExist($this->publicPath . 'sitemap-gone-1.xml');
        self::assertFileExists($this->publicPath . 'favicon.xml');
        self::assertSame([], glob($this->publicPath . '*.tmp'));
    }

    public function testRobotsTxtPointsAtTheIndex(): void
    {
        $this->generator()->generate();

        self::assertSame(
            'Sitemap: https://example.com/sitemap.xml' . PHP_EOL,
            file_get_contents($this->publicPath . 'robots.txt')
        );
    }

    /**
     * The files of two groups of one name would overwrite each other, so the generation refuses
     * before it writes anything. The name of the core is taken as well.
     *
     * @param list<string> $groups
     */
    #[DataProvider('duplicateGroups')]
    public function testTwoGroupsOfOneNameStopTheGeneration(array $groups, string $message): void
    {
        $providers = array_map(fn (string $group): SitemapUrlProviderInterface => $this->provider($group, static fn (): array => []), $groups);

        try {
            $this->generator(...$providers)->generate();
            self::fail('The generation accepted two groups of one name.');
        } catch (RuntimeException $exception) {
            self::assertSame($message, $exception->getMessage());
        }

        self::assertSame([], glob($this->publicPath . '*'));
    }

    /**
     * @return iterable<string, array{list<string>, string}>
     */
    public static function duplicateGroups(): iterable
    {
        yield 'two modules' => [['blog', 'blog'], 'Duplicate sitemap group name: blog'];
        yield 'a module and the core' => [['core'], 'Duplicate sitemap group name: core'];
    }

    /**
     * Without the address of the site every URL would be relative, which the protocol does not
     * allow. The lock is released all the same, so the next run is not blocked by this one.
     */
    public function testAnEmptyAddressOfTheSiteStopsTheGeneration(): void
    {
        ConfigRepository::init(['johncms' => ['homeurl' => ' / ']]);

        try {
            $this->generator()->generate();
            self::fail('The generation ran without the address of the site.');
        } catch (RuntimeException $exception) {
            self::assertSame('johncms.homeurl is empty.', $exception->getMessage());
        }

        self::assertSame([], glob($this->publicPath . '*'));

        ConfigRepository::init(['johncms' => ['homeurl' => 'https://example.com']]);
        $this->generator()->generate();

        self::assertFileExists($this->publicPath . 'sitemap.xml');
    }

    /**
     * The scheduler and the panel may both start a generation. The second one leaves without a
     * word rather than writing the same files at the same time.
     */
    public function testAGenerationAlreadyRunningIsNotJoined(): void
    {
        $lock = fopen($this->cachePath . 'sitemap.lock', 'c+');
        self::assertNotFalse($lock);
        self::assertTrue(flock($lock, LOCK_EX | LOCK_NB));

        try {
            $this->generator()->generate();
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }

        self::assertSame([], glob($this->publicPath . '*'));
    }

    private function generator(SitemapUrlProviderInterface ...$providers): SitemapGenerator
    {
        return new SitemapGenerator(
            new CoreUrlsProvider(),
            $providers,
            new RobotsTxtUpdater($this->publicPath),
            $this->publicPath,
            $this->cachePath,
        );
    }

    /**
     * @param Closure(string): iterable<SitemapUrlEntry> $entries
     */
    private function provider(string $group, Closure $entries): SitemapUrlProviderInterface
    {
        return new class ($group, $entries) implements SitemapUrlProviderInterface {
            public function __construct(
                private readonly string $group,
                private readonly Closure $entries,
            ) {
            }

            public function groupName(): string
            {
                return $this->group;
            }

            public function getEntries(string $homeUrl): iterable
            {
                return ($this->entries)($homeUrl);
            }
        };
    }

    /**
     * @return iterable<SitemapUrlEntry>
     */
    private static function entries(string $homeUrl, int $count): iterable
    {
        for ($i = 1; $i <= $count; ++$i) {
            yield new SitemapUrlEntry($homeUrl . '/page/' . $i);
        }
    }

    /**
     * @return list<string>
     */
    private function locations(string $file): array
    {
        $xml = simplexml_load_file($this->publicPath . $file);
        self::assertNotFalse($xml, $file . ' is not well-formed XML.');
        $xml->registerXPathNamespace('s', 'http://www.sitemaps.org/schemas/sitemap/0.9');

        return array_map(static fn (SimpleXMLElement $loc): string => (string) $loc, $xml->xpath('//s:loc') ?: []);
    }
}
