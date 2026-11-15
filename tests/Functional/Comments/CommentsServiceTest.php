<?php

declare(strict_types=1);

namespace Tests\Functional\Comments;

use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\Authorization\StaffTitles;
use Johncms\Auth\CurrentUser;
use Johncms\Comments\Application\CommentAccess;
use Johncms\Comments\Application\CommentExcerpt;
use Johncms\Comments\Application\CommentReplyNotifier;
use Johncms\Comments\Application\CommentsService;
use Johncms\Comments\Application\Exceptions\CommentAccessDeniedException;
use Johncms\Comments\Application\Exceptions\CommentNotFoundException;
use Johncms\Comments\Application\Exceptions\CommentValidationException;
use Johncms\Comments\CommentSubjectDTO;
use Johncms\Comments\CommentSubjects;
use Johncms\Comments\Domain\CommentRepositoryInterface;
use Johncms\Content\ContentRendererInterface;
use Johncms\Content\Html\HtmlFragment;
use Johncms\Database\TransactionManagerInterface;
use Johncms\EditorUploads\EditorUploadConsumers;
use Johncms\EditorUploads\EditorUploadRepositoryInterface;
use Johncms\EditorUploads\EditorUploads;
use Johncms\EditorUploads\ImageSources;
use Johncms\Files\FileStore;
use Johncms\Http\UploadedFileDTO;
use Johncms\Notifications\NotificationRepositoryInterface;
use Johncms\Image\StoreEditorImageUseCase;
use Johncms\Security\AntifloodCheckerInterface;
use Johncms\Security\ClientInfoDTO;
use Johncms\Security\HtmlSanitizerInterface;
use Johncms\System\Utility\EditorContentNormalizer;
use Johncms\Users\IgnoreListCheckerInterface;
use Johncms\Users\Repository\UserRepositoryInterface;
use Johncms\Users\User;
use Johncms\Utils\DateFormatterInterface;
use RuntimeException;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;
use Tests\Support\TestCommentSubject;
use Tests\Support\VanishingCommentRepository;

/**
 * The comments of the core against the real database, on a subject of the test.
 *
 * The visitor is whoever the last request was made by; what waits for the commit of a deletion
 * runs at once, since the suite rolls every test back instead of committing it.
 */
final class CommentsServiceTest extends FunctionalTestCase
{
    private const string TYPE = 'test_subject';
    private const int OBJECT = 7;
    private const int OTHER_OBJECT = 8;

    private TestCommentSubject $subject;

    private CommentsService $comments;

    private EditorUploads $uploads;

    /** @var list<int> */
    private array $storedFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new TestCommentSubject();
        $this->build();
    }

    protected function tearDown(): void
    {
        $this->container()->get(FileStore::class)->deleteMany($this->storedFiles);
        $this->storedFiles = [];

        parent::tearDown();
    }

    public function testACommentIsStoredWithItsAuthorAndCounted(): void
    {
        $author = $this->signIn(FunctionalUserFactory::create());

        $id = $this->comments->add($this->object(), '<p>Hello</p>', new ClientInfoDTO('10.0.0.1', '', 'Test agent'));

        $row = Capsule::table('comments')->where('id', $id)->first();
        self::assertNotNull($row);
        self::assertSame(self::TYPE, $row->subject_type);
        self::assertSame(self::OBJECT, (int) $row->subject_id);
        self::assertSame($author->id, (int) $row->user_id);
        self::assertSame($author->name, $row->author_name);
        self::assertSame('10.0.0.1', $row->author_ip);
        self::assertSame([[self::OBJECT, 1]], $this->subject->changes);
        self::assertSame(1, $this->komm($author->id));
        self::assertGreaterThan(0, (int) Capsule::table('users')->where('id', $author->id)->value('lastpost'));
    }

    public function testAGuestCannotWrite(): void
    {
        // A request without a session: the visitor of the previous test is gone.
        $this->handleRequest('/');

        $this->expectException(CommentAccessDeniedException::class);
        $this->comments->add($this->object(), '<p>Hello</p>', $this->client());
    }

    public function testAClosedDiscussionTakesNoComment(): void
    {
        $this->signIn(FunctionalUserFactory::create());

        $this->expectException(CommentAccessDeniedException::class);
        $this->comments->add($this->object(open: false), '<p>Hello</p>', $this->client());
    }

    public function testABanOfTheKindTheSubjectNamesClosesTheDiscussion(): void
    {
        $user = FunctionalUserFactory::create();
        Fixture::insert('cms_ban_users', ['user_id' => $user->id, 'ban_type' => 10, 'ban_time' => time() + 3600, 'ban_while' => time(), 'ban_reason' => 'Spam']);
        $this->signIn($user);

        $this->expectException(CommentAccessDeniedException::class);
        $this->comments->add($this->object(), '<p>Hello</p>', $this->client());
    }

    public function testAnEmptyTextIsRefusedAndAPictureAloneIsAComment(): void
    {
        $user = $this->signIn(FunctionalUserFactory::create());
        try {
            $this->comments->add($this->object(), '<p>&nbsp;</p>', $this->client());
            self::fail('An empty comment must be refused.');
        } catch (CommentValidationException) {
        }

        $picture = $this->upload($user->id);
        $id = $this->comments->add($this->object(), '<p><img src="' . $picture . '"></p>', $this->client());

        self::assertTrue(Capsule::table('comments')->where('id', $id)->exists());
    }

    public function testATooLongTextIsRefused(): void
    {
        $this->signIn(FunctionalUserFactory::create());

        $this->expectException(CommentValidationException::class);
        $this->comments->add($this->object(), '<p>' . str_repeat('a', CommentsService::MAX_LENGTH + 1) . '</p>', $this->client());
    }

    public function testTheSameTextTwiceInARowIsRefused(): void
    {
        $this->signIn(FunctionalUserFactory::create());
        $this->comments->add($this->object(), '<p>Hello</p>', $this->client());
        $this->allowNextPost();

        $this->expectException(CommentValidationException::class);
        $this->comments->add($this->object(), '<p>HELLO</p>', $this->client());
    }

    public function testWritingTooOftenIsRefused(): void
    {
        $user = $this->signIn(FunctionalUserFactory::create());
        $this->comments->add($this->object(), '<p>One</p>', $this->client());
        // The account the antiflood reads is the one the request loaded: it learns the new time
        // from the next request.
        $this->signIn($user);

        $this->expectException(CommentValidationException::class);
        $this->comments->add($this->object(), '<p>Two</p>', $this->client());
    }

    public function testADiscussionIsReadOldestFirstAndACommentKnowsItsPage(): void
    {
        $author = FunctionalUserFactory::create()->id;
        $later = $this->insertComment($author, '2026-01-02 10:00:00');
        $earlier = $this->insertComment($author, '2026-01-01 10:00:00');
        $last = $this->insertComment($author, '2026-01-03 10:00:00');

        $page = $this->comments->page($this->object(), 10, 0);

        self::assertSame([$earlier, $later, $last], array_map(static fn($comment): int => $comment->id, $page->comments));
        self::assertSame([1, 2, 3], array_map(static fn($comment): int => $comment->number, $page->comments));
        self::assertSame(2, $this->comments->pageOf($this->object(), $last, 2));
        self::assertSame(1, $this->comments->pageOf($this->object(), $earlier, 2));
    }

    public function testACommentOfAnotherDiscussionIsNotFound(): void
    {
        $author = FunctionalUserFactory::create()->id;
        $ofOtherObject = $this->insertComment($author, subjectId: self::OTHER_OBJECT);
        $ofOtherType = $this->insertComment($author, type: 'other_subject');
        $this->signIn(FunctionalUserFactory::createSupervisor());

        foreach ([$ofOtherObject, $ofOtherType] as $id) {
            try {
                $this->comments->delete($this->object(), $id);
                self::fail('A comment of another discussion must not be found through this one.');
            } catch (CommentNotFoundException) {
            }
        }

        self::assertSame(2, Capsule::table('comments')->whereIn('id', [$ofOtherObject, $ofOtherType])->count());
    }

    public function testTheAuthorEditsForFiveMinutesAndTheModeratorAlways(): void
    {
        $author = FunctionalUserFactory::create();
        $fresh = $this->insertComment($author->id, gmdate('Y-m-d H:i:s'));
        $old = $this->insertComment($author->id, gmdate('Y-m-d H:i:s', time() - CommentAccess::AUTHOR_EDIT_SECONDS - 60));
        $this->signIn($author);

        $this->comments->edit($this->object(), $fresh, '<p>Edited</p>');
        try {
            $this->comments->edit($this->object(), $old, '<p>Too late</p>');
            self::fail('The author must not edit after the window.');
        } catch (CommentAccessDeniedException) {
        }

        $moderator = $this->signIn(FunctionalUserFactory::createSupervisor());
        $this->comments->edit($this->object(), $old, '<p>By the moderator</p>');

        $row = Capsule::table('comments')->where('id', $old)->first();
        self::assertSame('<p>By the moderator</p>', $row->text);
        self::assertSame($moderator->id, (int) $row->edited_by);
        self::assertSame($moderator->name, $row->editor_name);
        self::assertSame(1, (int) $row->edit_count);
        self::assertSame('<p>Edited</p>', Capsule::table('comments')->where('id', $fresh)->value('text'));
    }

    public function testTheOwnerEditsAndDeletesOnlyWhereTheSubjectLetsThem(): void
    {
        $owner = FunctionalUserFactory::create();
        $id = $this->insertComment(FunctionalUserFactory::create()->id, gmdate('Y-m-d H:i:s', time() - 3600));
        $this->signIn($owner);

        foreach ([fn() => $this->comments->edit($this->object(ownerId: $owner->id), $id, '<p>x</p>'), fn() => $this->comments->delete($this->object(ownerId: $owner->id), $id)] as $action) {
            try {
                $action();
                self::fail('The owner must not act where the subject does not let them.');
            } catch (CommentAccessDeniedException) {
            }
        }

        $this->comments->edit($this->object(ownerId: $owner->id, ownerCanEdit: true), $id, '<p>By the owner</p>');
        $this->comments->delete($this->object(ownerId: $owner->id, ownerCanDelete: true), $id);

        self::assertFalse(Capsule::table('comments')->where('id', $id)->exists());
    }

    public function testDeletingACommentUncountsItAndDeletesItsPictures(): void
    {
        $author = $this->signIn(FunctionalUserFactory::create());
        $picture = $this->upload($author->id);
        $id = $this->comments->add($this->object(), '<p><img src="' . $picture . '"></p>', $this->client());
        $fileId = (int) Capsule::table('editor_uploads')->where('owner_type', 'comment')->where('owner_id', $id)->value('file_id');
        self::assertGreaterThan(0, $fileId);
        $this->subject->changes = [];

        $this->signIn(FunctionalUserFactory::createSupervisor());
        $this->comments->delete($this->object(), $id);

        self::assertFalse(Capsule::table('comments')->where('id', $id)->exists());
        self::assertNull($this->container()->get(FileStore::class)->find($fileId));
        self::assertSame([[self::OBJECT, -1]], $this->subject->changes);
        self::assertSame(0, $this->komm($author->id));
    }

    public function testACommentDeletedMeanwhileByAnotherRequestIsNotUncountedTwice(): void
    {
        $author = FunctionalUserFactory::create(['komm' => 5]);
        $id = $this->insertComment($author->id);
        $this->build(new VanishingCommentRepository($this->container()->get(CommentRepositoryInterface::class)));
        $this->signIn(FunctionalUserFactory::createSupervisor());

        $this->comments->delete($this->object(), $id);

        self::assertSame(5, $this->komm($author->id));
        self::assertSame([], $this->subject->changes);
    }

    public function testACommentDeletedMeanwhileTakesNoEditAndNoPicture(): void
    {
        $id = $this->insertComment(FunctionalUserFactory::create()->id);
        $moderator = $this->signIn(FunctionalUserFactory::createSupervisor());
        $picture = $this->upload($moderator->id);
        $this->build(new VanishingCommentRepository($this->container()->get(CommentRepositoryInterface::class)));

        try {
            $this->comments->edit($this->object(), $id, '<p><img src="' . $picture . '"></p>');
            self::fail('The edit of a comment deleted meanwhile must be refused.');
        } catch (CommentNotFoundException) {
        }

        self::assertFalse(Capsule::table('editor_uploads')->where('owner_type', 'comment')->where('owner_id', $id)->exists());
        self::assertTrue(Capsule::table('editor_uploads')->where('user_id', $moderator->id)->whereNull('owner_type')->exists());
    }

    public function testTheLatestFeedLeavesHiddenObjectsOutBeforePaging(): void
    {
        $author = FunctionalUserFactory::create()->id;
        $visible = $this->insertComment($author, '2026-01-01 10:00:00');
        for ($i = 0; $i < 5; $i++) {
            $this->insertComment($author, '2026-01-02 10:00:0' . $i, subjectId: TestCommentSubject::HIDDEN);
        }

        $latest = $this->comments->latest('test_subject', 3, 0);

        self::assertSame([$visible], array_map(static fn($item): int => $item->comment->id, $latest));
        self::assertSame(1, $this->comments->countOfType('test_subject'));
    }

    public function testAnOrdinaryUserCannotDelete(): void
    {
        $id = $this->insertComment(FunctionalUserFactory::create()->id);
        $this->signIn(FunctionalUserFactory::create());

        $this->expectException(CommentAccessDeniedException::class);
        $this->comments->delete($this->object(), $id);
    }

    public function testDeletingTheAuthorRemovesTheirCommentsUnderThisSubjectOnly(): void
    {
        $author = FunctionalUserFactory::create(['komm' => 4]);
        $first = $this->insertComment($author->id);
        $second = $this->insertComment($author->id);
        $ofOther = $this->insertComment(FunctionalUserFactory::create()->id);
        $elsewhere = $this->insertComment($author->id, subjectId: self::OTHER_OBJECT);
        $this->signIn(FunctionalUserFactory::createSupervisor());

        self::assertSame(2, $this->comments->deleteAuthor($this->object(), $first));

        self::assertSame([$ofOther, $elsewhere], $this->remaining([$first, $second, $ofOther, $elsewhere]));
        self::assertSame([[self::OBJECT, -2]], $this->subject->changes);
        self::assertSame(2, $this->komm($author->id));
    }

    public function testAFailingCounterOfTheModuleRollsTheCommentBack(): void
    {
        $author = $this->signIn(FunctionalUserFactory::create());
        $this->subject->failing = true;

        try {
            $this->comments->add($this->object(), '<p>Hello</p>', $this->client());
            self::fail('The failure of the module must reach the caller.');
        } catch (RuntimeException $exception) {
            self::assertSame('The counter failed.', $exception->getMessage());
        }

        self::assertSame(0, Capsule::table('comments')->where('subject_type', self::TYPE)->count());
        self::assertSame(0, $this->komm($author->id));
    }

    public function testTheCommentsOfAnAccountGoWithItAndTheObjectsCountThemNoMore(): void
    {
        $user = FunctionalUserFactory::create();
        $kept = $this->insertComment(FunctionalUserFactory::create()->id);
        $this->insertComment($user->id);
        $this->insertComment($user->id);
        $this->insertComment($user->id, subjectId: self::OTHER_OBJECT);

        $this->comments->deleteForUser($user->id);

        self::assertSame(0, Capsule::table('comments')->where('user_id', $user->id)->count());
        self::assertTrue(Capsule::table('comments')->where('id', $kept)->exists());
        self::assertEqualsCanonicalizing([[self::OBJECT, -2], [self::OTHER_OBJECT, -1]], $this->subject->changes);
    }

    public function testTheCommentsOfDeletedObjectsGoAndTheirAuthorsAreUncounted(): void
    {
        $author = FunctionalUserFactory::create(['komm' => 3]);
        $this->insertComment($author->id);
        $this->insertComment($author->id);
        $kept = $this->insertComment($author->id, subjectId: self::OTHER_OBJECT);

        $this->comments->deleteForSubjects(self::TYPE, [self::OBJECT]);

        self::assertSame([$kept], array_map('intval', Capsule::table('comments')->where('subject_type', self::TYPE)->pluck('id')->all()));
        self::assertSame(1, $this->komm($author->id));
        self::assertSame([], $this->subject->changes);
    }

    private function build(?CommentRepositoryInterface $repository = null): void
    {
        $container = $this->container();
        $subjects = new CommentSubjects([$this->subject]);
        $transactions = new class implements TransactionManagerInterface {
            public function run(callable $operation): mixed
            {
                return Capsule::connection()->transaction($operation);
            }

            public function afterCommit(callable $callback): void
            {
                $callback();
            }
        };
        $this->uploads = new EditorUploads(
            consumers: $container->get(EditorUploadConsumers::class),
            uploads: $container->get(EditorUploadRepositoryInterface::class),
            storeEditorImage: $container->get(StoreEditorImageUseCase::class),
            files: $container->get(FileStore::class),
            transactions: $transactions,
            imageSources: $container->get(ImageSources::class),
        );
        $this->comments = new CommentsService(
            comments: $repository ?? $container->get(CommentRepositoryInterface::class),
            subjects: $subjects,
            access: new CommentAccess(
                subjects: $subjects,
                currentUser: $container->get(CurrentUser::class),
                access: $container->get(AccessCheckerInterface::class),
                ignoreList: $container->get(IgnoreListCheckerInterface::class),
            ),
            uploads: $this->uploads,
            transactions: $transactions,
            currentUser: $container->get(CurrentUser::class),
            users: $container->get(UserRepositoryInterface::class),
            staff: $container->get(StaffTitles::class),
            content: $container->get(ContentRendererInterface::class),
            sanitizer: $container->get(HtmlSanitizerInterface::class),
            normalizer: $container->get(EditorContentNormalizer::class),
            antiflood: $container->get(AntifloodCheckerInterface::class),
            dates: $container->get(DateFormatterInterface::class),
            excerpt: $excerpt = new CommentExcerpt(new HtmlFragment()),
            replyNotifier: new CommentReplyNotifier(
                notifications: $container->get(NotificationRepositoryInterface::class),
                ignoreList: $container->get(IgnoreListCheckerInterface::class),
                excerpt: $excerpt,
            ),
        );
    }

    public function testAnAnswerKeepsTheCommentItAnswersAndTellsItsAuthor(): void
    {
        $answered = FunctionalUserFactory::create();
        $parent = $this->insertComment($answered->id, text: '<blockquote><p>Somebody else</p></blockquote><p>The first words</p>');
        $author = $this->signIn(FunctionalUserFactory::create());

        $id = $this->comments->add($this->object(), '<p>An answer</p>', $this->client(), $parent);

        self::assertSame($parent, (int) Capsule::table('comments')->where('id', $id)->value('reply_to_id'));
        $notification = Capsule::table('notifications')->where('user_id', $answered->id)->first();
        self::assertNotNull($notification);
        self::assertSame(CommentReplyNotifier::MODULE, $notification->module);
        self::assertSame(CommentReplyNotifier::EVENT, $notification->event_type);
        self::assertSame($author->id, (int) $notification->sender_id);
        self::assertSame($id, (int) $notification->entity_id);
        self::assertSame([
            'user_name'     => $author->name,
            'subject_title' => 'An object',
            'reply_url'     => '/test/' . self::OBJECT . '/comments/comment/' . $id,
            'message'       => 'An answer',
        ], json_decode((string) $notification->fields, true));

        $view = $this->comments->page($this->object(), 10, 0)->comments[1];
        self::assertNotNull($view->replyTo);
        self::assertSame($parent, $view->replyTo->id);
        self::assertSame($answered->name, $view->replyTo->authorName);
        // The quote is somebody else's words: the excerpt starts with the comment's own.
        self::assertSame('The first words', $view->replyTo->excerpt);
        self::assertSame('/test/' . self::OBJECT . '/comments/comment/' . $parent, $view->replyTo->url);
        self::assertNull($this->comments->page($this->object(), 10, 0)->comments[0]->replyTo);
    }

    public function testAnAnswerToACommentOfAnotherDiscussionAnswersNobody(): void
    {
        $other = FunctionalUserFactory::create();
        $elsewhere = $this->insertComment($other->id, subjectId: self::OTHER_OBJECT);
        $this->signIn(FunctionalUserFactory::create());

        $id = $this->comments->add($this->object(), '<p>An answer</p>', $this->client(), $elsewhere);

        self::assertNull(Capsule::table('comments')->where('id', $id)->value('reply_to_id'));
        self::assertFalse(Capsule::table('notifications')->where('user_id', $other->id)->exists());
    }

    public function testNobodyIsToldOfTheirOwnAnswerNorBySomebodyTheyIgnore(): void
    {
        $author = FunctionalUserFactory::create();
        $own = $this->insertComment($author->id);
        $this->signIn($author);
        $this->comments->add($this->object(), '<p>To myself</p>', $this->client(), $own);

        $ignoring = FunctionalUserFactory::create();
        $ignored = $this->insertComment($ignoring->id);
        Fixture::insert('cms_contact', ['user_id' => $ignoring->id, 'from_id' => $author->id, 'ban' => 1, 'time' => time()]);
        $this->allowNextPost();
        $this->comments->add($this->object(), '<p>To who ignores me</p>', $this->client(), $ignored);

        self::assertFalse(Capsule::table('notifications')->whereIn('user_id', [$author->id, $ignoring->id])->exists());
        self::assertSame(2, Capsule::table('comments')->whereNotNull('reply_to_id')->where('user_id', $author->id)->count());
    }

    public function testDeletingAnAnswerTakesItsNotificationBackAndDeletingTheAnsweredOneKeepsTheAnswer(): void
    {
        $answered = FunctionalUserFactory::create();
        $parent = $this->insertComment($answered->id);
        $this->signIn(FunctionalUserFactory::create());
        $first = $this->comments->add($this->object(), '<p>One answer</p>', $this->client(), $parent);
        $this->allowNextPost();
        $second = $this->comments->add($this->object(), '<p>Another answer</p>', $this->client(), $parent);
        $this->signIn(FunctionalUserFactory::createSupervisor());

        $this->comments->delete($this->object(), $first);

        self::assertSame([$second], array_map('intval', Capsule::table('notifications')->where('user_id', $answered->id)->pluck('entity_id')->all()));

        $this->comments->delete($this->object(), $parent);

        self::assertSame([$second], $this->remaining([$second]));
        self::assertNull(Capsule::table('comments')->where('id', $second)->value('reply_to_id'));
        self::assertNull($this->comments->page($this->object(), 10, 0)->comments[0]->replyTo);
    }

    private function object(bool $open = true, ?int $ownerId = null, bool $ownerCanEdit = false, bool $ownerCanDelete = false): CommentSubjectDTO
    {
        return new CommentSubjectDTO(
            type: self::TYPE,
            id: self::OBJECT,
            title: 'An object',
            url: '/test/' . self::OBJECT,
            discussionUrl: '/test/' . self::OBJECT . '/comments',
            ownerId: $ownerId,
            open: $open,
            ownerCanDelete: $ownerCanDelete,
            ownerCanEdit: $ownerCanEdit,
        );
    }

    /**
     * The visitor of what follows is the one this request was made by. The account is read again,
     * so a change made to it before shows.
     */
    private function signIn(User $user): User
    {
        $this->handleRequest('/', cookies: $this->actingAs($user));
        self::assertSame($user->id, $this->container()->get(CurrentUser::class)->id());

        return $user;
    }

    /** The antiflood measures from the last post; move it back so the next one is not refused for it. */
    private function allowNextPost(): void
    {
        $user = $this->container()->get(CurrentUser::class)->user();
        $user->lastpost = 0;
    }

    private function upload(int $userId): string
    {
        $image = imagecreatetruecolor(3, 3);
        imagefill($image, 0, 0, (int) imagecolorallocate($image, random_int(0, 255), random_int(0, 255), random_int(0, 255)));
        $path = sys_get_temp_dir() . '/johncms-comment-picture-' . bin2hex(random_bytes(6)) . '.png';
        imagepng($image, $path);
        imagedestroy($image);

        try {
            $file = $this->uploads->store(new UploadedFileDTO('picture.png', 'image/png', (int) filesize($path), $path, UPLOAD_ERR_OK), 'comment', $userId);
        } finally {
            unlink($path);
        }
        $this->storedFiles[] = $file->id;

        return $file->url;
    }

    private function insertComment(
        int $userId,
        ?string $createdAt = null,
        int $subjectId = self::OBJECT,
        string $type = self::TYPE,
        string $text = '<p>A comment</p>',
    ): int {
        return Fixture::insert('comments', [
            'subject_type' => $type,
            'subject_id'   => $subjectId,
            'user_id'      => $userId,
            'text'         => $text,
            'author_name'  => 'Author',
            'created_at'   => $createdAt ?? gmdate('Y-m-d H:i:s'),
        ]);
    }

    /**
     * @param list<int> $ids
     * @return list<int>
     */
    private function remaining(array $ids): array
    {
        return array_map('intval', Capsule::table('comments')->whereIn('id', $ids)->orderBy('id')->pluck('id')->all());
    }

    private function komm(int $userId): int
    {
        return (int) Capsule::table('users')->where('id', $userId)->value('komm');
    }

    private function client(): ClientInfoDTO
    {
        return new ClientInfoDTO('127.0.0.1', '', 'Test');
    }
}
