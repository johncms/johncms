<?php

declare(strict_types=1);

namespace Tests\Unit\View\CommentFeed;

use Johncms\View\CommentFeed\CommentFeed;
use Johncms\View\CommentFeed\CommentFeedSourceInterface;
use Johncms\View\CommentFeed\FeedComment;
use PHPUnit\Framework\TestCase;

final class CommentFeedTest extends TestCase
{
    public function testTheCommentsOfAllSourcesAreMergedTheNewestFirst(): void
    {
        $feed = new CommentFeed([
            $this->source($this->comment('files', 50), $this->comment('files', 10)),
            $this->source($this->comment('news', 40), $this->comment('news', 30), $this->comment('news', 20)),
        ]);

        self::assertSame(['files:50', 'news:40', 'news:30', 'news:20', 'files:10'], $this->listed($feed->page(10)));
        self::assertSame(5, $feed->count());
    }

    public function testAPageIsCutFromTheMergedList(): void
    {
        $feed = new CommentFeed([
            $this->source($this->comment('files', 50), $this->comment('files', 10)),
            $this->source($this->comment('news', 40), $this->comment('news', 30), $this->comment('news', 20)),
        ]);

        self::assertSame(['news:30', 'news:20'], $this->listed($feed->page(2, 2)));
        self::assertSame(['files:10'], $this->listed($feed->page(2, 4)));
    }

    public function testTheExcerptIsTheTextAsPlainWordsOnOneLine(): void
    {
        $comment = new FeedComment('News', 'Title', '/news/1', 1, 'Author', "<p>Hello,&nbsp;<b>world</b></p>\n<p>Again &amp; again</p>", 0);

        self::assertSame('Hello, world Again & again', $comment->excerpt());
    }

    public function testALongTextIsCut(): void
    {
        $comment = new FeedComment('News', 'Title', '/news/1', 1, 'Author', str_repeat('слово ', 100), 0);

        self::assertSame(300, mb_strwidth($comment->excerpt()));
        self::assertStringEndsWith('…', $comment->excerpt());
    }

    private function comment(string $section, int $time): FeedComment
    {
        return new FeedComment($section, 'Subject', '/' . $section, 1, 'Author', 'Text', $time);
    }

    /**
     * @param list<FeedComment> $comments
     * @return list<string>
     */
    private function listed(array $comments): array
    {
        return array_map(static fn (FeedComment $comment): string => $comment->section . ':' . $comment->time, $comments);
    }

    private function source(FeedComment ...$comments): CommentFeedSourceInterface
    {
        return new readonly class ($comments) implements CommentFeedSourceInterface {
            /**
             * @param list<FeedComment> $comments
             */
            public function __construct(private array $comments)
            {
            }

            public function latestComments(int $limit): array
            {
                return array_slice($this->comments, 0, $limit);
            }

            public function countComments(): int
            {
                return count($this->comments);
            }
        };
    }
}
