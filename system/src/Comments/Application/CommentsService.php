<?php

declare(strict_types=1);

namespace Johncms\Comments\Application;

use DateTimeImmutable;
use DateTimeZone;
use Johncms\Auth\Authorization\StaffTitles;
use Johncms\Auth\CurrentUser;
use Johncms\Comments\Application\DTO\CommentAuthorDTO;
use Johncms\Comments\Application\DTO\CommentPageDTO;
use Johncms\Comments\Application\DTO\CommentViewDTO;
use Johncms\Comments\Application\DTO\LatestCommentDTO;
use Johncms\Comments\Application\Exceptions\CommentAccessDeniedException;
use Johncms\Comments\Application\Exceptions\CommentNotFoundException;
use Johncms\Comments\Application\Exceptions\CommentValidationException;
use Johncms\Comments\CommentEditorUploads;
use Johncms\Comments\CommentSubjectDTO;
use Johncms\Comments\CommentSubjects;
use Johncms\Comments\Domain\Comment;
use Johncms\Comments\Domain\CommentRepositoryInterface;
use Johncms\Content\ContentContext;
use Johncms\Content\ContentRendererInterface;
use Johncms\Database\TransactionManagerInterface;
use Johncms\EditorUploads\EditorUploadBindException;
use Johncms\EditorUploads\EditorUploads;
use Johncms\Security\AntifloodCheckerInterface;
use Johncms\Security\ClientInfoDTO;
use Johncms\Security\HtmlSanitizerInterface;
use Johncms\System\Utility\EditorContentNormalizer;
use Johncms\Users\Repository\UserRepositoryInterface;
use Johncms\Utils\DateFormatterInterface;

/**
 * The comments of the core: what a discussion shows, and every change made to it.
 *
 * A change of the comments, the counter the module keeps on its object, the number of comments of
 * the author and the pictures of the text are written in one transaction. The files of the
 * pictures go once it commits — or, for the deletions a module asks for inside its own
 * transaction, once that one does.
 */
final readonly class CommentsService
{
    /** Characters of plain text one comment may hold. */
    public const int MAX_LENGTH = 10000;

    private const string DATE_FORMAT = 'Y-m-d H:i:s';

    public function __construct(
        private CommentRepositoryInterface $comments,
        private CommentSubjects $subjects,
        private CommentAccess $access,
        private EditorUploads $uploads,
        private TransactionManagerInterface $transactions,
        private CurrentUser $currentUser,
        private UserRepositoryInterface $users,
        private StaffTitles $staff,
        private ContentRendererInterface $content,
        private HtmlSanitizerInterface $sanitizer,
        private EditorContentNormalizer $normalizer,
        private AntifloodCheckerInterface $antiflood,
        private DateFormatterInterface $dates,
    ) {
    }

    public function count(CommentSubjectDTO $subject): int
    {
        return $this->comments->count($subject->type, $subject->id);
    }

    public function page(CommentSubjectDTO $subject, int $limit, int $offset): CommentPageDTO
    {
        $rows = $this->comments->getPage($subject->type, $subject->id, $limit, $offset);
        $authorCounts = $this->access->canDelete($subject)
            ? $this->comments->countByAuthors($subject->type, $subject->id, $this->authorIds($rows))
            : [];

        $comments = [];
        foreach ($this->views($rows) as $index => $view) {
            $comments[] = $view($subject, $offset + $index + 1, $authorCounts);
        }

        $refusal = $this->access->writeRefusal($subject);

        return new CommentPageDTO(comments: $comments, canWrite: $refusal === null, refusal: $refusal ?? '');
    }

    /** The current visitor opened the discussion; the module marks what was unread as read. */
    public function viewed(CommentSubjectDTO $subject): void
    {
        $this->subjects->get($subject->type)?->discussionViewed($subject);
    }

    /**
     * The newest comments on every object of the kind the visitor may see, for a feed or a
     * review of the moderator. An object hidden from the visitor leaves its comments out.
     *
     * @return list<LatestCommentDTO>
     */
    public function latest(string $subjectType, int $limit, int $offset): array
    {
        $kind = $this->subjects->get($subjectType);
        if ($kind === null) {
            return [];
        }

        $rows = $this->comments->getLatest($subjectType, $limit, $offset);
        $subjects = [];
        $latest = [];
        foreach ($this->views($rows) as $index => $view) {
            $row = $rows[$index];
            $subject = $subjects[$row->subject_id] ??= $kind->find($row->subject_id);
            if ($subject === null) {
                continue;
            }
            $latest[] = new LatestCommentDTO(
                subject: $subject,
                comment: $view($subject, 0, []),
                authorId: $row->user_id,
                html: $row->text,
                writtenAt: $this->timestamp($row->created_at),
            );
        }

        return $latest;
    }

    public function countOfType(string $subjectType): int
    {
        return $this->comments->countOfType($subjectType);
    }

    /**
     * What a comment shows, for each row: a function of the subject it is shown under, its
     * position, and how many comments each author left there.
     *
     * @param list<Comment> $rows
     * @return list<callable(CommentSubjectDTO, int, array<int, int>): CommentViewDTO>
     */
    private function views(array $rows): array
    {
        $users = $this->users->findMany($this->authorIds($rows));
        $this->staff->preload(array_keys($users));
        $canSeeOrigin = $this->access->canSeeOrigin();

        $views = [];
        foreach ($rows as $row) {
            $user = $users[$row->user_id] ?? null;
            $views[] = fn(CommentSubjectDTO $subject, int $number, array $authorCounts): CommentViewDTO => new CommentViewDTO(
                id: $row->id,
                number: $number,
                text: $this->content->render($row->text, new ContentContext(adminSmilies: $row->staff_smilies)),
                createdAt: $this->dates->format($this->timestamp($row->created_at)),
                author: $user === null ? null : new CommentAuthorDTO(
                    id: $user->id,
                    name: $user->name,
                    online: $user->is_online,
                    rightsName: $this->staff->titleFor($user->id),
                ),
                authorName: $row->author_name,
                editedAt: $row->edited_at === null ? '' : $this->dates->format($this->timestamp($row->edited_at)),
                editorName: $row->editor_name ?? '',
                editCount: $row->edit_count,
                canEdit: $this->access->canEdit($subject, $row),
                canDelete: $this->access->canDelete($subject),
                authorCommentCount: $authorCounts[$row->user_id] ?? 1,
                origin: $canSeeOrigin ? [
                    'ip'           => $row->author_ip ?? '',
                    'ip_via_proxy' => $row->author_ip_via_proxy ?? '',
                    'user_agent'   => $row->author_user_agent ?? '',
                ] : null,
            );
        }

        return $views;
    }

    /**
     * @param list<Comment> $rows
     * @return list<int>
     */
    private function authorIds(array $rows): array
    {
        return array_values(array_unique(array_filter(array_map(static fn(Comment $row): int => $row->user_id, $rows))));
    }

    /**
     * @throws CommentNotFoundException
     */
    public function find(CommentSubjectDTO $subject, int $commentId): Comment
    {
        return $this->comments->find($subject->type, $subject->id, $commentId) ?? throw new CommentNotFoundException();
    }

    /**
     * The page of the discussion the comment is on.
     *
     * @throws CommentNotFoundException
     */
    public function pageOf(CommentSubjectDTO $subject, int $commentId, int $perPage): int
    {
        return intdiv($this->comments->countBefore($this->find($subject, $commentId)), max(1, $perPage)) + 1;
    }

    /**
     * @throws CommentAccessDeniedException
     * @throws CommentValidationException
     */
    public function add(CommentSubjectDTO $subject, string $text, ClientInfoDTO $client): int
    {
        $this->access->ensureCanWrite($subject);
        $html = $this->prepare($text);

        $flood = $this->antiflood->getRemainingSeconds();
        if ($flood > 0) {
            throw new CommentValidationException(
                sprintf(d__('system', 'You cannot write so often. Wait %d seconds.'), $flood)
            );
        }

        $user = $this->currentUser->user();
        $latest = $this->comments->findLatestOfUser($subject->type, $user->id);
        if ($latest !== null && mb_strtolower($latest->text) === mb_strtolower($html)) {
            throw new CommentValidationException(d__('system', 'Message already exists'));
        }

        return $this->transactions->run(function () use ($subject, $html, $client, $user): int {
            $comment = $this->comments->create([
                'subject_type'        => $subject->type,
                'subject_id'          => $subject->id,
                'user_id'             => $user->id,
                'text'                => $html,
                'author_name'         => $user->name,
                'author_ip'           => $client->ip,
                'author_ip_via_proxy' => $client->ipViaProxy === '' ? null : $client->ipViaProxy,
                'author_user_agent'   => $client->userAgent,
                'staff_smilies'       => $this->staff->isStaff($user->id),
                'created_at'          => gmdate(self::DATE_FORMAT),
            ]);
            $this->bindPictures($comment->id, $user->id, $html);
            $this->subjects->get($subject->type)?->commentsChanged($subject->id, 1);
            $this->comments->changeUserCommentCount($user->id, 1);
            $this->comments->touchLastPost($user->id, time());

            return $comment->id;
        });
    }

    /**
     * @throws CommentNotFoundException
     * @throws CommentAccessDeniedException
     * @throws CommentValidationException
     */
    public function edit(CommentSubjectDTO $subject, int $commentId, string $text): void
    {
        $comment = $this->find($subject, $commentId);
        $this->access->ensureCanEdit($subject, $comment);
        $html = $this->prepare($text);
        $editor = $this->currentUser->user();

        $this->transactions->run(function () use ($comment, $html, $editor): void {
            $this->comments->update($comment, [
                'text'        => $html,
                'edited_at'   => gmdate(self::DATE_FORMAT),
                'edited_by'   => $editor->id,
                'editor_name' => $editor->name,
                'edit_count'  => $comment->edit_count + 1,
            ]);
            $this->bindPictures($comment->id, $editor->id, $html);
        });
    }

    /**
     * @throws CommentNotFoundException
     * @throws CommentAccessDeniedException
     */
    public function delete(CommentSubjectDTO $subject, int $commentId): void
    {
        $comment = $this->find($subject, $commentId);
        $this->access->ensureCanDelete($subject);

        $this->transactions->run(function () use ($subject, $comment): void {
            $this->remove([$comment->id => $comment->user_id]);
            $this->subjects->get($subject->type)?->commentsChanged($subject->id, -1);
        });
    }

    /**
     * Every comment the author of this one left under the subject.
     *
     * @return int How many were deleted.
     * @throws CommentNotFoundException
     * @throws CommentAccessDeniedException
     */
    public function deleteAuthor(CommentSubjectDTO $subject, int $commentId): int
    {
        $comment = $this->find($subject, $commentId);
        $this->access->ensureCanDelete($subject);

        return $this->transactions->run(function () use ($subject, $comment): int {
            $removed = $this->comments->getOfAuthor($subject->type, $subject->id, $comment->user_id);
            $this->remove($removed);
            $this->subjects->get($subject->type)?->commentsChanged($subject->id, -count($removed));

            return count($removed);
        });
    }

    /**
     * The objects are being deleted by their module: their comments go with them. Meant to run
     * inside the transaction of that deletion; the counters of the objects are left alone.
     *
     * @param list<int> $subjectIds
     * @return int How many comments were deleted.
     */
    public function deleteForSubjects(string $subjectType, array $subjectIds): int
    {
        return $this->transactions->run(function () use ($subjectType, $subjectIds): int {
            $removed = $this->comments->getOfSubjects($subjectType, $subjectIds);
            $this->remove($removed);

            return count($removed);
        });
    }

    /**
     * The comments of an account go: the objects count them no more, and their pictures are
     * deleted with them.
     */
    public function deleteForUser(int $userId): void
    {
        $this->transactions->run(function () use ($userId): void {
            $rows = $this->comments->getOfUser($userId);
            $perSubject = [];
            foreach ($rows as $row) {
                $key = $row['subject_type'] . ':' . $row['subject_id'];
                $perSubject[$key] ??= ['type' => $row['subject_type'], 'id' => $row['subject_id'], 'count' => 0];
                $perSubject[$key]['count']++;
            }

            $ids = array_column($rows, 'id');
            $this->comments->delete($ids);
            $this->uploads->detach(CommentEditorUploads::OWNER_TYPE, $ids);
            foreach ($perSubject as $subject) {
                // The module of a kind may be switched off: its objects are not counted then.
                $this->subjects->get($subject['type'])?->commentsChanged($subject['id'], -$subject['count']);
            }
        });
    }

    public function countForUser(int $userId): int
    {
        return $this->comments->countOfUser($userId);
    }

    /**
     * The text as it is stored: the empty edges the editor leaves trimmed, the HTML cleaned.
     *
     * @throws CommentValidationException
     */
    private function prepare(string $text): string
    {
        $html = $this->sanitizer->sanitize($this->normalizer->trimEdgeEmptyBlocks(trim($text)));

        // A picture or a video alone is a comment; only a text that shows nothing is empty.
        if ($html === '' || $this->content->renderOrNull($html) === null) {
            throw new CommentValidationException(d__('system', 'Enter the comment text'));
        }
        if (mb_strlen($this->sanitizer->toPlainText($html)) > self::MAX_LENGTH) {
            throw new CommentValidationException(
                sprintf(d__('system', 'The comment is too long: at most %d characters.'), self::MAX_LENGTH)
            );
        }

        return $html;
    }

    /**
     * @throws CommentValidationException
     */
    private function bindPictures(int $commentId, int $userId, string $html): void
    {
        try {
            $this->uploads->bind(CommentEditorUploads::OWNER_TYPE, $commentId, $userId, $html);
        } catch (EditorUploadBindException $exception) {
            throw new CommentValidationException($exception->getMessage(), 0, $exception);
        }
    }

    /**
     * Delete the comments, their pictures, and what their authors are counted.
     *
     * @param array<int, int> $authorsById
     */
    private function remove(array $authorsById): void
    {
        if ($authorsById === []) {
            return;
        }

        $ids = array_keys($authorsById);
        $this->comments->delete($ids);
        $this->uploads->detach(CommentEditorUploads::OWNER_TYPE, $ids);
        foreach (array_count_values($authorsById) as $userId => $count) {
            $this->comments->changeUserCommentCount($userId, -$count);
        }
    }

    private function timestamp(string $utc): int
    {
        return (new DateTimeImmutable($utc, new DateTimeZone('UTC')))->getTimestamp();
    }
}
