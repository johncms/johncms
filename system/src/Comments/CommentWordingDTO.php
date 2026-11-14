<?php

declare(strict_types=1);

namespace Johncms\Comments;

use Closure;

/**
 * What a discussion calls itself and its messages to the visitor, translated. The comments of a
 * file are "comments"; the entries of a guestbook are the same thing under another word, and the
 * subject that discusses them says so here.
 *
 * The texts that depend on a number are functions of it, so that the plural forms of the language
 * are chosen by the module that knows the words.
 */
final readonly class CommentWordingDTO
{
    /**
     * @param string                       $heading            What the discussion is called: in the breadcrumbs, as the title.
     * @param string                       $titled             The title of the page: "Comments: %s", the name of the object put in.
     * @param Closure(int): string         $count              "%d comments" for the number given.
     * @param string                       $empty              The discussion has nothing yet.
     * @param string                       $write              The label of the form.
     * @param string                       $loginNotice        A guest is asked to sign in; trusted markup with a link to the login page.
     * @param string                       $added              A message was added.
     * @param string                       $saved              A message was saved after editing.
     * @param string                       $deleted            A message was deleted.
     * @param string                       $confirmDelete      The question before deleting one.
     * @param Closure(int, string): string $confirmDeleteAuthor The question before deleting all of an author: their count and name.
     * @param Closure(int): string         $deletedOfAuthor    How many of an author were deleted.
     * @param string                       $editTitle          The title of the editing page.
     * @param string                       $emptyText          The text sent is empty.
     */
    public function __construct(
        public string $heading,
        public string $titled,
        private Closure $count,
        public string $empty,
        public string $write,
        public string $loginNotice,
        public string $added,
        public string $saved,
        public string $deleted,
        public string $confirmDelete,
        private Closure $confirmDeleteAuthor,
        private Closure $deletedOfAuthor,
        public string $editTitle,
        public string $emptyText,
    ) {
    }

    /** The words of a discussion of comments, which most objects have. */
    public static function comments(): self
    {
        return new self(
            heading: d__('system', 'Comments'),
            titled: d__('system', 'Comments: %s'),
            count: static fn(int $count): string => sprintf(dn__('system', '%d comment', '%d comments', $count), $count),
            empty: d__('system', 'No comments yet. Be the first.'),
            write: d__('system', 'Write a comment'),
            loginNotice: d__('system', 'You are not logged in. You must be <a href="/login/">logged in</a> to comment.'),
            added: d__('system', 'The comment was added'),
            saved: d__('system', 'The comment was saved'),
            deleted: d__('system', 'The comment was deleted'),
            confirmDelete: d__('system', 'Delete this comment?'),
            confirmDeleteAuthor: static fn(int $count, string $name): string => sprintf(
                dn__('system', 'Delete %d comment of %s here?', 'Delete all %d comments of %s here?', $count),
                $count,
                $name
            ),
            deletedOfAuthor: static fn(int $count): string => sprintf(dn__('system', '%d comment was deleted', '%d comments were deleted', $count), $count),
            editTitle: d__('system', 'Edit comment'),
            emptyText: d__('system', 'Enter the comment text'),
        );
    }

    public function count(int $count): string
    {
        return ($this->count)($count);
    }

    public function confirmDeleteAuthor(int $count, string $name): string
    {
        return ($this->confirmDeleteAuthor)($count, $name);
    }

    public function deletedOfAuthor(int $count): string
    {
        return ($this->deletedOfAuthor)($count);
    }
}
