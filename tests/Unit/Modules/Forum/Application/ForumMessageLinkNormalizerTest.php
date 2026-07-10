<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Forum\Application;

use Johncms\Modules\Forum\Application\Services\ForumMessageLinkNormalizer;
use PHPUnit\Framework\TestCase;

final class ForumMessageLinkNormalizerTest extends TestCase
{
    private const HOSTS = ['johncms.com', 'johncmscom.loc'];

    private function normalize(string $text, ?callable $topicUrlResolver = null): string
    {
        return (new ForumMessageLinkNormalizer())->normalize($text, self::HOSTS, $topicUrlResolver);
    }

    public function testUnwrapsInternalShowPostToCanonicalRoute(): void
    {
        $in = '<a href="https://johncmscom.loc/redirect/?url=https%3A%2F%2Fjohncms.com%2Fforum%2F%3Fact%3Dshow_post%26id%3D553928">#</a>';
        self::assertSame('<a href="/forum/post/553928/">#</a>', $this->normalize($in));
    }

    public function testUnwrapsInternalShowPostWithLegacyFrontController(): void
    {
        $in = '<a href="https://johncmscom.loc/redirect/?url=http%3A%2F%2Fjohncms.com%2Fforum%2Findex.php%3Fact%3Dshow_post%26id%3D549484">#</a>';
        self::assertSame('<a href="/forum/post/549484/">#</a>', $this->normalize($in));
    }

    public function testUnwrapsProtocolRelativeInternalLink(): void
    {
        $in = '<a href="https://johncmscom.loc/redirect/?url=%2F%2Fjohncms.com%2Fforum%2Findex.php%3Fact%3Dshow_post%26id%3D509755">#</a>';
        self::assertSame('<a href="/forum/post/509755/">#</a>', $this->normalize($in));
    }

    public function testResolvesInternalTopicLinkToSeoUrl(): void
    {
        $resolver = static fn (int $id, ?int $page): ?string =>
            '/forum/general/my-topic-' . $id . '/' . ($page !== null && $page > 1 ? '?page=' . $page : '');

        $in = '<a href="https://johncmscom.loc/redirect/?url=https%3A%2F%2Fjohncms.com%2Fforum%2Findex.php%3Ftype%3Dtopic%26id%3D5334%26page%3D5">t</a>';
        self::assertSame('<a href="/forum/general/my-topic-5334/?page=5">t</a>', $this->normalize($in, $resolver));
    }

    public function testFallsBackToUnwrappedTopicLinkWhenResolverReturnsNull(): void
    {
        $resolver = static fn (int $id, ?int $page): ?string => null;

        $in = '<a href="https://johncmscom.loc/redirect/?url=https%3A%2F%2Fjohncms.com%2Fforum%2Findex.php%3Ftype%3Dtopic%26id%3D5334%26page%3D5">t</a>';
        self::assertSame('<a href="/forum/?type=topic&amp;id=5334&amp;page=5">t</a>', $this->normalize($in, $resolver));
    }

    public function testUnwrapsOtherInternalLinkKeepingQueryAndDroppingFrontController(): void
    {
        $in = '<a href="https://johncmscom.loc/redirect/?url=https%3A%2F%2Fjohncms.com%2Fforum%2Findex.php%3Ftype%3Dtopics%26id%3D77">s</a>';
        self::assertSame('<a href="/forum/?type=topics&amp;id=77">s</a>', $this->normalize($in));
    }

    public function testUnwrapsInternalProfileLink(): void
    {
        $in = '<a href="https://johncmscom.loc/redirect/?url=https%3A%2F%2Fjohncms.com%2Fprofile%2F%3Fuser%3D38422">kantry</a>';
        self::assertSame('<a href="/profile/?user=38422">kantry</a>', $this->normalize($in));
    }

    public function testKeepsExternalRedirectLinkWrapped(): void
    {
        $in = '<a href="https://johncmscom.loc/redirect/?url=https%3A%2F%2Fexample.com%2Fforum%2F%3Fact%3Dshow_post%26id%3D5">ext</a>';
        self::assertSame($in, $this->normalize($in));
    }

    public function testNormalizesPlainTextShowPostLink(): void
    {
        $in = 'читать это johncms.com/forum/index.php?act=show_post&amp;id=341019 + ещё';
        self::assertSame('читать это johncms.com/forum/post/341019/ + ещё', $this->normalize($in));
    }

    public function testLeavesUnrelatedTextUntouched(): void
    {
        $in = 'no links here, just plain text';
        self::assertSame($in, $this->normalize($in));
    }
}
