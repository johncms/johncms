<?php

declare(strict_types=1);

namespace Johncms\Comments\Application;

use Johncms\Auth\CurrentUser;
use Johncms\Comments\CommentSubjectDTO;
use Johncms\Http\Pagination\Pagination;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Session;

/**
 * What a discussion shows on a page: the discussion page of the core, or the page of a module that
 * shows the discussion within itself (`CommentSubjectDTO::$embeddedIn`). Both render the
 * `@theme/components/comments/discussion.twig` component with these variables, and the regions of
 * the live list with `@theme/pages/comments-live.twig`.
 */
final readonly class CommentDiscussionView
{
    public const string FLASH_MESSAGE = 'comments.message';
    public const string FLASH_ERROR = 'comments.error';
    public const string FLASH_TEXT = 'comments.text';
    public const string FLASH_REPLY_TO = 'comments.reply_to';
    public const string FLASH_ADDED = 'comments.added';

    /** Id of the discussion on the page; the links of the pagination lead to it. */
    public const string ANCHOR = 'comments';

    public function __construct(
        private CommentsService $comments,
        private PaginationFactory $paginationFactory,
        private Session $session,
        private CurrentUser $currentUser,
    ) {
    }

    public function pagination(CommentSubjectDTO $subject): Pagination
    {
        return $this->paginationFactory->create(total: $this->comments->count($subject), fragment: self::ANCHOR);
    }

    /**
     * The page of comments the pagination is on, what the visitor may do there, and the result of
     * their last action. Marks the discussion viewed.
     *
     * @return array<string, mixed>
     */
    public function data(CommentSubjectDTO $subject, Pagination $pagination): array
    {
        $page = $this->comments->page($subject, $pagination->getPerPage(), $pagination->getOffset());
        $this->comments->viewed($subject);

        return [
            'subject'    => $subject,
            'comments'   => $page->comments,
            'total'      => $pagination->getTotal(),
            'page'       => $pagination->getCurrentPage(),
            'pagination' => $pagination->hasPages() ? $pagination->render() : null,
            'can_write'  => $page->canWrite,
            'refusal'    => $this->currentUser->isValid() ? $page->refusal : '',
            'upload_url' => $subject->discussionUrl . '/upload',
            'message'    => $this->session->getFlash(self::FLASH_MESSAGE),
            'error'      => $this->session->getFlash(self::FLASH_ERROR),
            'added'      => $this->session->getFlash(self::FLASH_ADDED),
            // The live list keeps the text in the editor; the flash is still read so that it does
            // not come back on the next page shown.
            'text'       => $this->session->getFlash(self::FLASH_TEXT) ?? '',
            // A form sent back with an error still answers the comment it answered.
            'answered'   => $this->comments->answered($subject, (int) ($this->session->getFlash(self::FLASH_REPLY_TO) ?? 0)),
        ];
    }
}
