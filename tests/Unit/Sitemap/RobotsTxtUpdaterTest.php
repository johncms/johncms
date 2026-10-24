<?php

declare(strict_types=1);

namespace Tests\Unit\Sitemap;

use Johncms\Sitemap\RobotsTxtUpdater;
use PHPUnit\Framework\TestCase;

/**
 * robots.txt belongs to the administrator: the generation only keeps its Sitemap line pointing at
 * the index and leaves every other rule as it was written.
 */
final class RobotsTxtUpdaterTest extends TestCase
{
    private string $publicPath;

    protected function setUp(): void
    {
        $this->publicPath = sys_get_temp_dir() . DS . 'johncms-robots-' . uniqid() . DS;
        mkdir($this->publicPath, 0o777, true);
    }

    protected function tearDown(): void
    {
        exec('rm -rf ' . escapeshellarg($this->publicPath));
    }

    public function testAMissingFileIsCreatedWithTheSitemapLineAlone(): void
    {
        $this->update();

        self::assertSame($this->lines(['Sitemap: https://example.com/sitemap.xml']), $this->robots());
    }

    public function testTheRulesOfTheSiteAreKeptAndTheSitemapLineGoesLast(): void
    {
        $this->write(['User-agent: *', 'Disallow: /admin/', 'Allow: /', '']);

        $this->update();

        self::assertSame(
            $this->lines(['User-agent: *', 'Disallow: /admin/', 'Allow: /', 'Sitemap: https://example.com/sitemap.xml']),
            $this->robots()
        );
    }

    /**
     * Every Sitemap line goes, whatever its case, indentation or address: the site may have moved
     * to another one. A blank line between the rules stays, the blank lines at the end do not.
     */
    public function testEveryEarlierSitemapLineIsReplacedByOne(): void
    {
        $this->write([
            'Sitemap: http://old.example.com/sitemap.xml',
            'User-agent: *',
            'Disallow: /admin/',
            '  sitemap: https://example.com/sitemap-forum-1.xml',
            '',
            'User-agent: BadBot',
            'Disallow: /',
            '',
            '',
        ]);

        $this->update();

        self::assertSame(
            $this->lines([
                'User-agent: *',
                'Disallow: /admin/',
                '',
                'User-agent: BadBot',
                'Disallow: /',
                'Sitemap: https://example.com/sitemap.xml',
            ]),
            $this->robots()
        );
    }

    public function testTheLineEndingsOfTheFileAreRewrittenWithThoseOfTheServer(): void
    {
        file_put_contents($this->publicPath . 'robots.txt', "User-agent: *\r\nDisallow: /admin/\rAllow: /\r\n");

        $this->update();

        self::assertSame(
            $this->lines(['User-agent: *', 'Disallow: /admin/', 'Allow: /', 'Sitemap: https://example.com/sitemap.xml']),
            $this->robots()
        );
    }

    public function testASecondRunChangesNothing(): void
    {
        $this->write(['User-agent: *', 'Disallow: /admin/']);
        $this->update();
        $first = $this->robots();

        $this->update();

        self::assertSame($first, $this->robots());
        self::assertSame([], glob($this->publicPath . '*.tmp'));
    }

    private function update(): void
    {
        (new RobotsTxtUpdater($this->publicPath))->update('https://example.com');
    }

    /**
     * @param list<string> $lines
     */
    private function write(array $lines): void
    {
        file_put_contents($this->publicPath . 'robots.txt', implode(PHP_EOL, $lines));
    }

    private function robots(): string
    {
        return (string) file_get_contents($this->publicPath . 'robots.txt');
    }

    /**
     * @param list<string> $lines
     */
    private function lines(array $lines): string
    {
        return implode(PHP_EOL, $lines) . PHP_EOL;
    }
}
