<?php

declare(strict_types=1);

namespace Johncms\View\CommentFeed;

/**
 * One comment in the feed of the panel: who wrote what, when, under what, and where to read it in
 * its place.
 */
final readonly class FeedComment
{
    private const int EXCERPT_LENGTH = 300;

    /**
     * @param string $section  The part of the site the comment belongs to: "Downloads", "News".
     * @param string $subject  What it is a comment on: the name of the file, the title of the article.
     * @param string $url      The comments of that subject on the site, where they are answered and removed.
     * @param int    $authorId 0 for an account that no longer exists.
     * @param string $text     The text as stored: the HTML the site keeps, never escaped.
     */
    public function __construct(
        public string $section,
        public string $subject,
        public string $url,
        public int $authorId,
        public string $authorName,
        public string $text,
        public int $time,
    ) {
    }

    /**
     * The text as plain words on one line, cut to a length a list can hold. The template escapes it
     * like any other text.
     */
    public function excerpt(): string
    {
        $plain = trim((string) preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($this->text), ENT_QUOTES | ENT_HTML5, 'UTF-8')));

        return mb_strimwidth($plain, 0, self::EXCERPT_LENGTH, '…', 'UTF-8');
    }
}
