<?php

declare(strict_types=1);

namespace Tests\Unit\Content\Embed;

use Johncms\Content\Embed\YoutubeEmbedProvider;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class YoutubeEmbedProviderTest extends TestCase
{
    #[DataProvider('recognisedAddresses')]
    public function testTheAddressOfAVideoBecomesAPlayerOfThatVideo(string $url, string $expectedSource): void
    {
        $embed = (new YoutubeEmbedProvider())->embed($url);

        self::assertNotNull($embed);
        self::assertSame(YoutubeEmbedProvider::TEMPLATE, $embed->template);
        self::assertSame($expectedSource, $embed->parameters['src']);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function recognisedAddresses(): iterable
    {
        $player = 'https://www.youtube.com/embed/dQw4w9WgXcQ?rel=0';

        yield 'watch page' => ['https://www.youtube.com/watch?v=dQw4w9WgXcQ', $player];
        yield 'mobile' => ['https://m.youtube.com/watch?v=dQw4w9WgXcQ', $player];
        yield 'short link' => ['https://youtu.be/dQw4w9WgXcQ', $player];
        yield 'embed link' => ['https://www.youtube.com/embed/dQw4w9WgXcQ', $player];
        yield 'shorts' => ['https://www.youtube.com/shorts/dQw4w9WgXcQ', $player];
        yield 'seconds' => ['https://youtu.be/dQw4w9WgXcQ?t=90', $player . '&start=90'];
        yield 'hours, minutes and seconds' => ['https://youtu.be/dQw4w9WgXcQ?t=1h2m3s', $player . '&start=3723'];
    }

    #[DataProvider('foreignAddresses')]
    public function testAnAddressThatIsNotAVideoIsLeftToAnotherProvider(string $url): void
    {
        self::assertNull((new YoutubeEmbedProvider())->embed($url));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function foreignAddresses(): iterable
    {
        yield 'another site' => ['https://vimeo.com/12345'];
        // The host is compared against the list, so a name that merely ends in it is not YouTube.
        yield 'a host that only looks like it' => ['https://youtube.com.example.org/watch?v=dQw4w9WgXcQ'];
        yield 'a channel' => ['https://www.youtube.com/channel/UC1234567890'];
        yield 'the front page' => ['https://www.youtube.com/'];
        yield 'no id at all' => ['https://www.youtube.com/watch?list=PL123'];
    }

    /**
     * The id ends up in the address of an iframe. It is matched against what YouTube itself
     * accepts rather than trusted: the editor writes the link, and an editor is a user.
     */
    #[DataProvider('rejectedIds')]
    public function testAnIdThatIsNotAnIdIsRefused(string $url): void
    {
        self::assertNull((new YoutubeEmbedProvider())->embed($url));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function rejectedIds(): iterable
    {
        yield 'a quote' => ['https://www.youtube.com/watch?v=a"onload=alert(1)'];
        yield 'a path of its own' => ['https://www.youtube.com/watch?v=../../evil'];
        yield 'too short' => ['https://youtu.be/abc'];
    }

    public function testAnAddressThatIsNotAUrlIsRefused(): void
    {
        self::assertNull((new YoutubeEmbedProvider())->embed('not a url at all'));
        self::assertNull((new YoutubeEmbedProvider())->embed(''));
    }
}
