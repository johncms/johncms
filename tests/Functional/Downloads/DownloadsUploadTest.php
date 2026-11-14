<?php

declare(strict_types=1);

namespace Tests\Functional\Downloads;

use Dom\HTMLDocument;
use Johncms\Modules\Downloads\Domain\Models\DownloadFile;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

/**
 * Uploading a file: a rejected form keeps what was typed and says why beside the field; an
 * accepted one leads to its result, which a reload does not send again.
 */
final class DownloadsUploadTest extends FunctionalTestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = sys_get_temp_dir() . '/johncms-functional-' . bin2hex(random_bytes(4));
        mkdir($this->directory);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->directory . '/*') ?: []);
        rmdir($this->directory);

        parent::tearDown();
    }

    public function testARejectedFormKeepsWhatWasTyped(): void
    {
        $moderator = FunctionalUserFactory::createSupervisor();
        $category = Fixture::insert('download__category', ['name' => 'docs', 'slug' => 'docs', 'dir' => $this->directory, 'rus_name' => 'Documents']);

        $response = $this->handleRequest(
            '/downloads/upload/' . $category . '/',
            'POST',
            $this->withCsrfToken(['text' => 'My manual', 'name_link' => 'Get it']),
            cookies: $this->actingAs($moderator),
        );

        self::assertSame(422, $response->getStatusCode());
        $page = HTMLDocument::createFromString((string) $response->getContent(), LIBXML_NOERROR);
        self::assertNotNull($page->querySelector('#upload-fail.is-invalid'), 'The missing file is named beside its field.');
        self::assertSame('My manual', $page->querySelector('#upload-text')?->getAttribute('value'));
        self::assertSame('Get it', $page->querySelector('#upload-name_link')?->getAttribute('value'));
        self::assertSame(0, DownloadFile::query()->count());
    }

    public function testAnUploadShowsItsResultOnceAfterTheRedirect(): void
    {
        $user = FunctionalUserFactory::create();
        // field = 1: the members may upload into this folder, files of these extensions.
        $category = Fixture::insert('download__category', ['name' => 'docs', 'slug' => 'docs', 'dir' => $this->directory, 'rus_name' => 'Documents', 'field' => 1, 'text' => 'txt, zip']);
        $source = tempnam(sys_get_temp_dir(), 'upload');
        file_put_contents($source, 'a manual');
        $url = '/downloads/upload/' . $category . '/';
        $cookies = $this->actingAs($user);

        $response = $this->handleRequest(
            $url,
            'POST',
            $this->withCsrfToken(['text' => 'My manual', 'name_link' => 'Download']),
            cookies: $cookies,
            files: ['fail' => new UploadedFile($source, 'manual.txt', 'text/plain', null, true)],
        );

        self::assertSame(302, $response->getStatusCode());
        self::assertSame($url, $response->headers->get('Location'));
        $file = DownloadFile::query()->firstOrFail();
        self::assertSame(3, $file->type, 'The file of a member waits for a moderator.');
        self::assertFileExists($this->directory . '/' . $file->name);

        $result = HTMLDocument::createFromString((string) $this->handleRequest($url, cookies: $cookies)->getContent(), LIBXML_NOERROR);
        self::assertNotNull($result->querySelector('.site-dl-result'));
        self::assertStringContainsString('My manual', (string) $result->querySelector('.site-dl-result h2')?->textContent);

        $reloaded = HTMLDocument::createFromString((string) $this->handleRequest($url, cookies: $cookies)->getContent(), LIBXML_NOERROR);
        self::assertNull($reloaded->querySelector('.site-dl-result'), 'The result is shown once; a reload shows the form.');
        self::assertNotNull($reloaded->querySelector('form[name="file_upload_form"]'));
        self::assertSame(1, DownloadFile::query()->count());
    }

    public function testAVisitorWhoMayNotUploadIsRefused(): void
    {
        $category = Fixture::insert('download__category', ['name' => 'docs', 'slug' => 'docs', 'dir' => $this->directory, 'rus_name' => 'Documents']);

        self::assertSame(403, $this->handleRequest('/downloads/upload/' . $category . '/')->getStatusCode());
        self::assertSame(404, $this->handleRequest('/downloads/upload/99999/')->getStatusCode());
    }
}
