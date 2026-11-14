<?php

declare(strict_types=1);

namespace Johncms\Comments;

/**
 * An object of a module as its discussion sees it.
 */
final readonly class CommentSubjectDTO
{
    public CommentWordingDTO $wording;

    /**
     * @param string                                  $title         What is discussed, as plain text: the name of the file, of the article.
     * @param string                                  $url           Address of the object itself.
     * @param string                                  $discussionUrl Address of the discussion; the routes of CommentRoutes hang off it.
     * @param int|null                                $ownerId       Who the object belongs to, when it belongs to somebody.
     * @param bool                                    $open          Whether new comments are taken; reading stays possible when not.
     * @param bool                                    $ownerCanDelete Whether the owner removes comments under the object.
     * @param bool                                    $ownerCanEdit   Whether the owner edits them.
     * @param list<int>                               $bans          Kinds of ban that close the discussion to the banned.
     * @param list<array{name: string, url: string}>  $breadcrumbs   The way to the object, the object included, as plain text; the discussion adds itself.
     * @param string                                  $description   For the meta description of the discussion.
     * @param CommentWordingDTO|null                  $wording       What the discussion calls itself; null for comments.
     * @param string                                  $embeddedIn    Address of the page of the module that shows the discussion within itself; empty when the discussion has a page of its own.
     * @param bool                                    $moderator     Whether the current visitor moderates this discussion by a permission of the module, besides the moderators of all comments.
     * @param bool                                    $authorCanDelete Whether the author removes their own comment.
     */
    public function __construct(
        public string $type,
        public int $id,
        public string $title,
        public string $url,
        public string $discussionUrl,
        public ?int $ownerId = null,
        public bool $open = true,
        public bool $ownerCanDelete = false,
        public bool $ownerCanEdit = false,
        public array $bans = [1, 10],
        public array $breadcrumbs = [],
        public string $description = '',
        ?CommentWordingDTO $wording = null,
        public string $embeddedIn = '',
        public bool $moderator = false,
        public bool $authorCanDelete = false,
    ) {
        $this->wording = $wording ?? CommentWordingDTO::comments();
    }
}
