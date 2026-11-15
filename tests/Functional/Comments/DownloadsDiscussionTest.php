<?php

declare(strict_types=1);

namespace Tests\Functional\Comments;

use Dom\HTMLDocument;
use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Files\FileStore;
use Johncms\Comments\Application\CommentAccess;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

/**
 * The discussion of a file of the downloads, served by the controller of the core comments:
 * real requests through the kernel, the routes the module declared and the templates of the theme.
 */
final class DownloadsDiscussionTest extends FunctionalTestCase
{
    /** @var list<string> Files the downloads point at; removed again in tearDown(). */
    private array $storedFiles = [];

    /** @var list<string> */
    private array $temporaryFiles = [];

    /** @var list<int> Pictures stored through the upload route. */
    private array $uploadedFiles = [];

    private int $fileId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fileId = $this->file();
    }

    protected function tearDown(): void
    {
        // The rows go away with the transaction, the files on the disk do not.
        $this->container()->get(FileStore::class)->deleteMany($this->uploadedFiles);
        $this->uploadedFiles = [];
        foreach ($this->storedFiles as $path) {
            unlink($path);
            rmdir(dirname($path));
        }
        foreach ($this->temporaryFiles as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }
        $this->storedFiles = [];
        $this->temporaryFiles = [];

        parent::tearDown();
    }

    public function testTheDiscussionIsReadOldestFirst(): void
    {
        $author = FunctionalUserFactory::create()->id;
        $later = $this->comment($author, '2026-01-02 10:00:00', '<p>Later</p>');
        $earlier = $this->comment($author, '2026-01-01 10:00:00', '<p>Earlier</p>');

        $response = $this->handleRequest($this->url());

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame(['comment-' . $earlier, 'comment-' . $later], $this->commentIds($this->page($response)));
    }

    public function testThePageCarriesItsTitleCanonicalAndRobots(): void
    {
        $empty = $this->page($this->handleRequest($this->url()));
        self::assertSame('noindex,follow', $empty->querySelector('meta[name="robots"]')?->getAttribute('content'));

        $this->comment(FunctionalUserFactory::create()->id);
        $page = $this->page($this->handleRequest($this->url()));

        self::assertNull($page->querySelector('meta[name="robots"]'));
        self::assertStringEndsWith($this->url(), (string) $page->querySelector('link[rel="canonical"]')?->getAttribute('href'));
        self::assertStringContainsString('A file', (string) $page->querySelector('title')?->textContent);
    }

    public function testASecondPageIsItsOwnCanonicalAndANonExistentOneRedirects(): void
    {
        $author = FunctionalUserFactory::create()->id;
        for ($i = 0; $i < 25; $i++) {
            $this->comment($author, gmdate('Y-m-d H:i:s', 1_700_000_000 + $i));
        }

        $page = $this->page($this->handleRequest($this->url() . '?page=2'));
        self::assertStringEndsWith($this->url() . '?page=2', (string) $page->querySelector('link[rel="canonical"]')?->getAttribute('href'));

        self::assertSame(Response::HTTP_FOUND, $this->handleRequest($this->url() . '?page=99')->getStatusCode());
    }

    public function testTheLiveListGetsTheRegionsOnly(): void
    {
        $this->comment(FunctionalUserFactory::create()->id);

        $content = (string) $this->handleRequest($this->url(), server: ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'])->getContent();

        self::assertStringNotContainsString('<html', $content);
        self::assertStringContainsString('data-live-list-region="comments"', $content);
        self::assertStringContainsString('data-live-list-region="comment-error"', $content);
        self::assertStringContainsString('<title>', $content);
    }

    public function testAnAddedCommentLeadsToItsPlaceAndIsCounted(): void
    {
        $author = FunctionalUserFactory::create();

        $response = $this->postForm($this->url(), ['text' => '<p>Hello</p>'], $author);

        $id = (int) Capsule::table('comments')->where('subject_type', 'downloads')->where('subject_id', $this->fileId)->value('id');
        self::assertGreaterThan(0, $id);
        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        self::assertSame($this->url() . '#comment-' . $id, $response->headers->get('Location'));
        self::assertSame(1, (int) Capsule::table('download__files')->where('id', $this->fileId)->value('comm_count'));
    }

    public function testARefusedCommentComesBackWithItsTextAndTheReason(): void
    {
        $author = FunctionalUserFactory::create();
        $cookies = $this->actingAs($author);

        $response = $this->handleRequest($this->url(), 'POST', $this->withCsrfToken(['text' => '<p></p>']), cookies: $cookies);
        self::assertSame($this->url() . '#comment-form', $response->headers->get('Location'));

        $page = $this->page($this->handleRequest($this->url(), cookies: $cookies));
        self::assertSame(d__('system', 'Enter the comment text'), trim((string) $page->querySelector('[data-live-list-region="comment-error"] .alert')?->textContent));
        self::assertSame(0, Capsule::table('comments')->where('subject_type', 'downloads')->count());
    }

    public function testARefusedAnswerStillAnswersItsComment(): void
    {
        $author = FunctionalUserFactory::create();
        $answered = $this->comment($author->id, text: '<p>The first words</p>');
        $ofOtherFile = $this->comment(FunctionalUserFactory::create()->id, subjectId: $this->file());
        $cookies = $this->actingAs(FunctionalUserFactory::create());

        $this->handleRequest($this->url(), 'POST', $this->withCsrfToken(['text' => '<p></p>', 'reply_to' => (string) $answered]), cookies: $cookies);
        $form = $this->page($this->handleRequest($this->url(), cookies: $cookies))->querySelector('[data-comment-form]');
        self::assertSame((string) $answered, $form?->querySelector('[data-comment-reply-field]')?->getAttribute('value'));
        $label = $form->querySelector('[data-comment-reply-label]');
        self::assertFalse($label?->hasAttribute('hidden'));
        self::assertSame($author->name, $label->querySelector('[data-comment-reply-name]')?->textContent, 'The name of the account, as under the answer.');
        self::assertSame('The first words', $label->querySelector('[data-comment-reply-excerpt]')?->textContent);

        $this->handleRequest($this->url(), 'POST', $this->withCsrfToken(['text' => '<p></p>', 'reply_to' => (string) $ofOtherFile]), cookies: $cookies);
        $form = $this->page($this->handleRequest($this->url(), cookies: $cookies))->querySelector('[data-comment-form]');
        self::assertSame('', $form?->querySelector('[data-comment-reply-field]')?->getAttribute('value'), 'A comment of another discussion is not recalled.');
        self::assertTrue($form->querySelector('[data-comment-reply-label]')?->hasAttribute('hidden'));
    }

    public function testAGuestAndABannedUserCannotPostDirectly(): void
    {
        $this->handleRequest($this->url(), 'POST', $this->withCsrfToken(['text' => '<p>From a guest</p>']));

        $banned = FunctionalUserFactory::create();
        Fixture::insert('cms_ban_users', ['user_id' => $banned->id, 'ban_type' => 1, 'ban_time' => time() + 3600, 'ban_while' => time(), 'ban_reason' => 'Spam']);
        $this->postForm($this->url(), ['text' => '<p>From the banned</p>'], $banned);

        self::assertSame(0, Capsule::table('comments')->where('subject_type', 'downloads')->count());
    }

    public function testAMissingFileHasNoDiscussion(): void
    {
        self::assertSame(Response::HTTP_NOT_FOUND, $this->handleRequest('/downloads/comments/999999')->getStatusCode());
    }

    public function testTheAddressesOfTheOldEngineLeadToTheList(): void
    {
        foreach (['?mod=del&item=1&yes', '?mod=reply&item=1', '?start=20'] as $query) {
            $response = $this->handleRequest($this->url() . $query);

            self::assertSame(Response::HTTP_MOVED_PERMANENTLY, $response->getStatusCode());
            self::assertSame($this->url(), $response->headers->get('Location'));
        }
    }

    public function testAPermalinkLeadsToThePageOfTheComment(): void
    {
        $user = FunctionalUserFactory::create();
        $ids = [];
        for ($i = 0; $i < 12; $i++) {
            $ids[] = $this->comment($user->id, gmdate('Y-m-d H:i:s', 1_700_000_000 + $i));
        }

        $response = $this->handleRequest($this->url() . '/comment/' . $ids[11], cookies: $this->actingAs($user));

        self::assertSame($this->url() . '?page=2#comment-' . $ids[11], $response->headers->get('Location'));
    }

    public function testTheAuthorEditsForFiveMinutesAndNotAfter(): void
    {
        $author = FunctionalUserFactory::create();
        $fresh = $this->comment($author->id, gmdate('Y-m-d H:i:s'));
        $old = $this->comment($author->id, gmdate('Y-m-d H:i:s', time() - CommentAccess::AUTHOR_EDIT_SECONDS - 60));
        $cookies = $this->actingAs($author);

        self::assertSame(Response::HTTP_OK, $this->handleRequest($this->url() . '/edit/' . $fresh, cookies: $cookies)->getStatusCode());
        $saved = $this->handleRequest($this->url() . '/edit/' . $fresh, 'POST', $this->withCsrfToken(['text' => '<p>Edited</p>']), cookies: $cookies);
        self::assertSame($this->url() . '/comment/' . $fresh, $saved->headers->get('Location'));
        self::assertSame('<p>Edited</p>', Capsule::table('comments')->where('id', $fresh)->value('text'));

        self::assertSame(Response::HTTP_FORBIDDEN, $this->handleRequest($this->url() . '/edit/' . $old, cookies: $cookies)->getStatusCode());
        $refused = $this->handleRequest($this->url() . '/edit/' . $old, 'POST', $this->withCsrfToken(['text' => '<p>Late</p>']), cookies: $cookies);
        self::assertSame(Response::HTTP_FORBIDDEN, $refused->getStatusCode());
        self::assertSame('<p>A comment</p>', Capsule::table('comments')->where('id', $old)->value('text'));

        $moderator = $this->actingAs(FunctionalUserFactory::createSupervisor());
        self::assertSame(Response::HTTP_OK, $this->handleRequest($this->url() . '/edit/' . $old, cookies: $moderator)->getStatusCode());
        self::assertNotNull($this->page($this->handleRequest($this->url() . '/edit/' . $old, cookies: $moderator))->querySelector('meta[name="robots"]'));
    }

    public function testACommentOfAnotherDiscussionIsNotFoundThroughThisOne(): void
    {
        $author = FunctionalUserFactory::create()->id;
        $otherFile = $this->file();
        $ofOtherFile = $this->comment($author, subjectId: $otherFile);
        $ofOtherType = Fixture::insert('comments', [
            'subject_type' => 'other',
            'subject_id'   => $this->fileId,
            'user_id'      => $author,
            'text'         => '<p>Elsewhere</p>',
            'created_at'   => gmdate('Y-m-d H:i:s'),
        ]);
        $moderator = FunctionalUserFactory::createSupervisor();

        foreach ([$ofOtherFile, $ofOtherType] as $id) {
            self::assertSame(Response::HTTP_NOT_FOUND, $this->handleRequest($this->url() . '/edit/' . $id, cookies: $this->actingAs($moderator))->getStatusCode());
            self::assertSame(
                Response::HTTP_NOT_FOUND,
                $this->postForm($this->url() . '/delete', ['comment_id' => $id, 'confirmed' => '1'], $moderator)->getStatusCode()
            );
        }

        self::assertSame(2, Capsule::table('comments')->whereIn('id', [$ofOtherFile, $ofOtherType])->count());
    }

    public function testDeletingAsksForConfirmationAndBelongsToTheModerator(): void
    {
        $author = FunctionalUserFactory::create();
        $id = $this->comment($author->id);
        Capsule::table('download__files')->where('id', $this->fileId)->update(['comm_count' => 1]);

        self::assertSame(Response::HTTP_FORBIDDEN, $this->postForm($this->url() . '/delete', ['comment_id' => $id, 'confirmed' => '1'], $author)->getStatusCode());
        $moderator = FunctionalUserFactory::createSupervisor();
        self::assertSame(Response::HTTP_CONFLICT, $this->postForm($this->url() . '/delete', ['comment_id' => $id], $moderator)->getStatusCode());
        self::assertTrue(Capsule::table('comments')->where('id', $id)->exists());

        $response = $this->postForm($this->url() . '/delete', ['comment_id' => $id, 'confirmed' => '1', 'page' => '1'], $moderator);

        self::assertSame($this->url() . '#comments', $response->headers->get('Location'));
        self::assertFalse(Capsule::table('comments')->where('id', $id)->exists());
        self::assertSame(0, (int) Capsule::table('download__files')->where('id', $this->fileId)->value('comm_count'));
    }

    public function testDeletingTheAuthorRemovesTheirCommentsUnderTheFile(): void
    {
        $author = FunctionalUserFactory::create()->id;
        $first = $this->comment($author);
        $this->comment($author);
        $ofOther = $this->comment(FunctionalUserFactory::create()->id);

        $this->postForm($this->url() . '/delete-author', ['comment_id' => $first, 'confirmed' => '1'], FunctionalUserFactory::createSupervisor());

        self::assertSame([$ofOther], array_map('intval', Capsule::table('comments')->where('subject_type', 'downloads')->pluck('id')->all()));
    }

    public function testAPictureIsUploadedByWhoeverMayWriteAndTakenByTheComment(): void
    {
        $guest = $this->upload([]);
        self::assertSame(Response::HTTP_FORBIDDEN, $guest->getStatusCode());

        $author = FunctionalUserFactory::create();
        $response = $this->upload($this->actingAs($author));
        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        $stored = json_decode((string) $response->getContent(), true);
        self::assertIsArray($stored);
        $this->uploadedFiles[] = (int) $stored['id'];
        self::assertTrue(Capsule::table('editor_uploads')->where('file_id', $stored['id'])->whereNull('owner_type')->exists());

        $this->postForm($this->url(), ['text' => '<p><img src="' . $stored['url'] . '"></p>'], $author);

        $comment = (int) Capsule::table('comments')->where('subject_type', 'downloads')->value('id');
        self::assertSame($comment, (int) Capsule::table('editor_uploads')->where('file_id', $stored['id'])->where('owner_type', 'comment')->value('owner_id'));
    }

    private function url(): string
    {
        return '/downloads/comments/' . $this->fileId;
    }

    private function file(): int
    {
        $directory = sys_get_temp_dir() . '/johncms-functional-' . bin2hex(random_bytes(4));
        mkdir($directory);
        $path = $directory . '/file.zip';
        file_put_contents($path, 'a download');
        $this->storedFiles[] = $path;

        // type 2 is a file of the catalogue; 0 and 1 are the categories.
        return Fixture::insert('download__files', ['type' => 2, 'name' => 'file.zip', 'dir' => $directory, 'rus_name' => 'A file']);
    }

    private function comment(int $userId, ?string $createdAt = null, string $text = '<p>A comment</p>', ?int $subjectId = null): int
    {
        return Fixture::insert('comments', [
            'subject_type' => 'downloads',
            'subject_id'   => $subjectId ?? $this->fileId,
            'user_id'      => $userId,
            'text'         => $text,
            'author_name'  => 'Author',
            'created_at'   => $createdAt ?? gmdate('Y-m-d H:i:s'),
        ]);
    }

    /**
     * @param array<string, string> $cookies
     */
    private function upload(array $cookies): Response
    {
        $image = imagecreatetruecolor(3, 3);
        imagefill($image, 0, 0, (int) imagecolorallocate($image, random_int(0, 255), random_int(0, 255), random_int(0, 255)));
        $path = sys_get_temp_dir() . '/johncms-discussion-' . bin2hex(random_bytes(6)) . '.png';
        imagepng($image, $path);
        imagedestroy($image);
        $this->temporaryFiles[] = $path;

        return $this->handleRequest(
            $this->url() . '/upload',
            'POST',
            $this->withCsrfToken(),
            cookies: $cookies,
            files: ['upload' => new UploadedFile($path, 'picture.png', 'image/png', null, true)]
        );
    }

    private function page(Response $response): HTMLDocument
    {
        return HTMLDocument::createFromString((string) $response->getContent(), LIBXML_NOERROR);
    }

    /**
     * @return list<string>
     */
    private function commentIds(HTMLDocument $page): array
    {
        $ids = [];
        foreach ($page->querySelectorAll('[data-comment]') as $comment) {
            $ids[] = (string) $comment->getAttribute('id');
        }

        return $ids;
    }
}
