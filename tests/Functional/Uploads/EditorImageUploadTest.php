<?php

declare(strict_types=1);

namespace Tests\Functional\Uploads;

use Johncms\Config\ConfigRepository;
use Johncms\Files\FileStore;
use Johncms\Security\Csrf;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\FunctionalUserFactory;

/**
 * A picture uploaded from the text editor, driven through a real request.
 *
 * The guestbook is the shortest way in — its editor uploads without a permission of its own —
 * but what is exercised is the core: every editor of the site stores through the same use case.
 */
final class EditorImageUploadTest extends FunctionalTestCase
{
    private const URL = '/guestbook/upload_file';

    /** @var list<int> */
    private array $storedFiles = [];

    protected function tearDown(): void
    {
        // The rows go away with the transaction, the files on the disk do not.
        foreach ($this->storedFiles as $id) {
            $this->container()->get(FileStore::class)->delete($id);
        }
        $this->storedFiles = [];

        parent::tearDown();
    }

    public function testAFileThatIsNotAPictureIsRefused(): void
    {
        $path = $this->temporaryFile('txt');
        file_put_contents($path, 'This is not a picture, whatever the name says.');

        $response = $this->upload($path, 'photo.jpg', 'image/jpeg');

        // Compared against the translated string rather than against the English one: the
        // suite runs in whatever language the site is configured in.
        self::assertSame(d__('system', 'Only images are allowed'), $this->errorMessage($response));
    }

    public function testAPictureHeavierThanTheLimitIsRefused(): void
    {
        $path = $this->picture(800, 600);

        $response = $this->withSetting('max_size', 1, fn() => $this->upload($path, 'photo.png', 'image/png'));

        self::assertSame(
            sprintf(d__('system', 'The file is larger than %d KB'), 1),
            $this->errorMessage($response)
        );
    }

    public function testAPictureLargerThanTheBoundsIsStoredScaledDown(): void
    {
        $path = $this->picture(2000, 1500);

        $response = $this->withSetting('max_width', 800, fn() => $this->upload($path, 'photo.png', 'image/png'));

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $stored = $this->decode($response);
        self::assertSame(1, $stored['uploaded'] ?? null);

        $this->storedFiles[] = $stored['id'];

        // Read back what actually landed on the disk: the scaling is the point of the change.
        $stream = $this->container()->get(FileStore::class)->openStream($stored['id']);
        self::assertNotNull($stream);
        $size = getimagesizefromstring((string) stream_get_contents($stream->stream));

        self::assertNotFalse($size);
        self::assertSame(800, $size[0]);
        self::assertSame(600, $size[1]);
    }

    private function upload(string $path, string $name, string $mimeType): Response
    {
        return $this->handleRequest(
            self::URL,
            'POST',
            ['csrf_token' => $this->container()->get(Csrf::class)->getToken()],
            cookies: $this->actingAs(FunctionalUserFactory::create()),
            files: ['upload' => new UploadedFile($path, $name, $mimeType, null, true)]
        );
    }

    /**
     * Run the request with one setting replaced, and put the configuration back afterwards: the
     * container is built once per process and every later test reads the same values.
     *
     * @template T
     * @param callable(): T $test
     * @return T
     */
    private function withSetting(string $key, int $value, callable $test): mixed
    {
        $config = ConfigRepository::all();

        $johncms = $config['johncms'];
        $johncms['editor_images'] = array_replace((array) ($johncms['editor_images'] ?? []), [$key => $value]);
        ConfigRepository::init(array_replace($config, ['johncms' => $johncms]));

        try {
            return $test();
        } finally {
            ConfigRepository::init($config);
        }
    }

    private function picture(int $width, int $height): string
    {
        $image = imagecreatetruecolor($width, $height);
        // A gradient rather than a flat fill: an empty picture compresses to almost nothing, and
        // the size limit is what one of the tests is about.
        for ($x = 0; $x < $width; $x++) {
            $color = imagecolorallocate($image, $x % 256, ($x * 3) % 256, ($x * 7) % 256);
            imageline($image, $x, 0, $x, $height, (int) $color);
        }

        $path = $this->temporaryFile('png');
        imagepng($image, $path);
        imagedestroy($image);

        return $path;
    }

    private function temporaryFile(string $extension): string
    {
        $path = (string) tempnam(sys_get_temp_dir(), 'johncms_test_') . '.' . $extension;
        register_shutdown_function(static function () use ($path): void {
            if (is_file($path)) {
                unlink($path);
            }
        });

        return $path;
    }

    private function errorMessage(Response $response): ?string
    {
        return $this->decode($response)['error']['message'] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(Response $response): array
    {
        return (array) json_decode((string) $response->getContent(), true);
    }
}
