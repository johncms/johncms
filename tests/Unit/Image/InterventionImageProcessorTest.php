<?php

declare(strict_types=1);

namespace Tests\Unit\Image;

use Johncms\Image\ImageProcessingException;
use Johncms\Image\ImagePosition;
use Johncms\Image\ImageProcessorInterface;
use Johncms\Image\InterventionImageProcessor;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The processor is the only place that knows the imaging library, so these tests are what
 * catches a major upgrade of it changing the shape of a stored picture. They assert on the
 * result — its size, its format, its corners — never on calls made to the library.
 *
 * The methods that no module of the CMS calls yet are covered here for the same reason: the
 * contract promises them to module authors, and nothing else would notice them breaking.
 */
final class InterventionImageProcessorTest extends TestCase
{
    private string $directory;
    private ImageProcessorInterface $processor;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'johncms-image-' . bin2hex(random_bytes(6));
        mkdir($this->directory, 0777, true);
        $this->processor = new InterventionImageProcessor();
    }

    protected function tearDown(): void
    {
        foreach (glob($this->directory . DIRECTORY_SEPARATOR . '*') ?: [] as $file) {
            unlink($file);
        }
        rmdir($this->directory);
    }

    public function testScaledDownFitsWithinTheBoundsAndKeepsTheProportions(): void
    {
        $source = $this->createImage('source.jpg', 800, 400);

        $this->processor->saveScaledDown($source, $target = $this->path('out.jpg'), 200, 200);

        self::assertSame([200, 100], $this->sizeOf($target));
    }

    public function testScaledDownScalesByWidthWhenNoHeightIsGiven(): void
    {
        $source = $this->createImage('source.jpg', 800, 400);

        $this->processor->saveScaledDown($source, $target = $this->path('out.jpg'), 240);

        self::assertSame([240, 120], $this->sizeOf($target));
    }

    /**
     * The CMS never enlarges what a visitor uploaded: a small picture blown up to the bounds
     * is a blurred picture and a bigger file, and nothing is gained.
     */
    public function testScaledDownLeavesAPictureSmallerThanTheBoundsAlone(): void
    {
        $source = $this->createImage('source.jpg', 100, 50);

        $this->processor->saveScaledDown($source, $target = $this->path('out.jpg'), 800, 600);

        self::assertSame([100, 50], $this->sizeOf($target));
    }

    public function testScaledDownRequiresAtLeastOneSide(): void
    {
        $source = $this->createImage('source.jpg', 100, 50);

        $this->expectException(ImageProcessingException::class);

        $this->processor->saveScaledDown($source, $this->path('out.jpg'));
    }

    /**
     * The format follows the extension of the target, which is what lets a caller store an
     * upload of any type under a fixed name.
     */
    public function testTheFormatComesFromTheExtensionOfTheTarget(): void
    {
        $source = $this->createImage('source.jpg', 100, 100);

        $this->processor->saveConverted($source, $target = $this->path('out.png'));

        self::assertSame('image/png', $this->mimeOf($target));
        self::assertSame([100, 100], $this->sizeOf($target));
    }

    /**
     * Quality has to reach the encoder. The library matches options against the parameter
     * names of the encoder it picked, so a positional argument is dropped without a word and
     * every picture of the site would quietly be written at the default quality.
     */
    public function testQualityReachesTheEncoder(): void
    {
        $source = $this->createDetailedImage('source.jpg', 400, 400);

        $this->processor->saveConverted($source, $low = $this->path('low.jpg'), 10);
        $this->processor->saveConverted($source, $high = $this->path('high.jpg'), 100);

        self::assertLessThan(filesize($high), filesize($low));
    }

    public function testCroppedFillsTheFrameExactly(): void
    {
        $source = $this->createImage('source.jpg', 800, 200);

        $this->processor->saveCropped($source, $target = $this->path('out.jpg'), 300, 300);

        self::assertSame([300, 300], $this->sizeOf($target));
    }

    /**
     * A crop keeps the part of the picture it is pointed at. The source is red on the left
     * half and blue on the right, so which half survives says where the crop was taken from.
     */
    public function testCroppedKeepsThePartTheGivenPositionPointsAt(): void
    {
        $source = $this->createHalvedImage('source.png', 400, 100);

        $this->processor->saveCropped($source, $left = $this->path('left.png'), 100, 100, ImagePosition::Left);
        $this->processor->saveCropped($source, $right = $this->path('right.png'), 100, 100, ImagePosition::Right);

        self::assertSame([255, 0, 0], $this->colorAt($left, 50, 50));
        self::assertSame([0, 0, 255], $this->colorAt($right, 50, 50));
    }

    public function testPaddedKeepsTheWholePictureAndFillsTheRestWithTheBackground(): void
    {
        $source = $this->createImage('source.png', 400, 100, [255, 0, 0]);

        $this->processor->savePadded($source, $target = $this->path('out.png'), 400, 400, '00ff00');

        self::assertSame([400, 400], $this->sizeOf($target));
        // The picture lands in the middle, the margins above and below it are the background.
        self::assertSame([255, 0, 0], $this->colorAt($target, 200, 200));
        self::assertSame([0, 255, 0], $this->colorAt($target, 200, 10));
        self::assertSame([0, 255, 0], $this->colorAt($target, 200, 390));
    }

    public function testPaddedLeavesTheMarginsTransparentWhenAskedTo(): void
    {
        $source = $this->createImage('source.png', 400, 100, [255, 0, 0]);

        $this->processor->savePadded(
            $source,
            $target = $this->path('out.png'),
            400,
            400,
            ImageProcessorInterface::TRANSPARENT
        );

        self::assertTrue($this->isTransparentAt($target, 200, 10));
    }

    public function testStretchedPullsThePictureToTheFrameWithoutKeepingTheProportions(): void
    {
        $source = $this->createImage('source.jpg', 800, 200);

        $this->processor->saveStretched($source, $target = $this->path('out.jpg'), 300, 300);

        self::assertSame([300, 300], $this->sizeOf($target));
    }

    public function testWatermarkIsLaidIntoTheCornerItIsGiven(): void
    {
        $source = $this->createImage('source.png', 200, 200, [255, 255, 255]);
        $watermark = $this->createImage('mark.png', 40, 40, [255, 0, 0]);

        $this->processor->saveWatermarked(
            $source,
            $target = $this->path('out.png'),
            $watermark,
            ImagePosition::TopLeft
        );

        self::assertSame([200, 200], $this->sizeOf($target));
        self::assertSame([255, 0, 0], $this->colorAt($target, 20, 20));
        // The opposite corner stays untouched.
        self::assertSame([255, 255, 255], $this->colorAt($target, 180, 180));
    }

    /**
     * The offset keeps the mark off the very edge. With it, the pixel the mark used to cover
     * belongs to the picture again.
     */
    public function testTheOffsetMovesTheWatermarkAwayFromTheEdge(): void
    {
        $source = $this->createImage('source.png', 200, 200, [255, 255, 255]);
        $watermark = $this->createImage('mark.png', 40, 40, [255, 0, 0]);

        $this->processor->saveWatermarked(
            $source,
            $target = $this->path('out.png'),
            $watermark,
            ImagePosition::TopLeft,
            offset: 50
        );

        self::assertSame([255, 255, 255], $this->colorAt($target, 20, 20));
        self::assertSame([255, 0, 0], $this->colorAt($target, 70, 70));
    }

    #[DataProvider('outOfRangeOpacities')]
    public function testAnOpacityOutsideTheRangeIsRejected(int $opacity): void
    {
        $source = $this->createImage('source.png', 100, 100);
        $watermark = $this->createImage('mark.png', 10, 10);

        $this->expectException(ImageProcessingException::class);

        $this->processor->saveWatermarked($source, $this->path('out.png'), $watermark, opacity: $opacity);
    }

    /**
     * @return array<string, array{int}>
     */
    public static function outOfRangeOpacities(): array
    {
        return ['below zero' => [-1], 'above a hundred' => [101]];
    }

    public function testTheBlurredThumbnailFillsTheFrameExactly(): void
    {
        $source = $this->createImage('source.jpg', 800, 200);

        $this->processor->saveBlurredThumbnail($source, $target = $this->path('out.jpg'), 400, 300);

        self::assertSame([400, 300], $this->sizeOf($target));
    }

    public function testAFileThatIsNotAPictureRaisesTheDomainException(): void
    {
        $source = $this->path('not-an-image.jpg');
        file_put_contents($source, 'certainly not a picture');

        $this->expectException(ImageProcessingException::class);

        $this->processor->saveScaledDown($source, $this->path('out.jpg'), 100, 100);
    }

    public function testAMissingSourceRaisesTheDomainException(): void
    {
        $this->expectException(ImageProcessingException::class);

        $this->processor->saveScaledDown($this->path('nothing-here.jpg'), $this->path('out.jpg'), 100, 100);
    }

    /**
     * @param array{int, int, int} $color
     */
    private function createImage(string $name, int $width, int $height, array $color = [120, 120, 120]): string
    {
        $image = imagecreatetruecolor($width, $height);
        imagefill($image, 0, 0, imagecolorallocate($image, ...$color));

        return $this->write($image, $name);
    }

    /**
     * A picture where every pixel differs from its neighbours. A flat colour compresses to the
     * same handful of bytes at any quality, so only a detailed source tells the two apart.
     */
    private function createDetailedImage(string $name, int $width, int $height): string
    {
        $image = imagecreatetruecolor($width, $height);

        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                $color = imagecolorallocate($image, ($x * 7 + $y * 13) % 256, ($x * 3) % 256, ($y * 5) % 256);
                imagesetpixel($image, $x, $y, $color);
            }
        }

        return $this->write($image, $name);
    }

    /**
     * Red on the left half, blue on the right: which half a crop kept is then a single pixel
     * to look at.
     */
    private function createHalvedImage(string $name, int $width, int $height): string
    {
        $image = imagecreatetruecolor($width, $height);
        imagefilledrectangle($image, 0, 0, intdiv($width, 2) - 1, $height - 1, imagecolorallocate($image, 255, 0, 0));
        imagefilledrectangle($image, intdiv($width, 2), 0, $width - 1, $height - 1, imagecolorallocate($image, 0, 0, 255));

        return $this->write($image, $name);
    }

    private function write(\GdImage $image, string $name): string
    {
        $path = $this->path($name);

        if (str_ends_with($name, '.png')) {
            imagepng($image, $path);
        } else {
            imagejpeg($image, $path, 100);
        }

        return $path;
    }

    private function path(string $name): string
    {
        return $this->directory . DIRECTORY_SEPARATOR . $name;
    }

    /**
     * @return array{int, int}
     */
    private function sizeOf(string $path): array
    {
        $info = getimagesize($path);
        self::assertNotFalse($info, sprintf('"%s" is not a readable image', $path));

        return [$info[0], $info[1]];
    }

    private function mimeOf(string $path): string
    {
        $info = getimagesize($path);
        self::assertNotFalse($info, sprintf('"%s" is not a readable image', $path));

        return $info['mime'];
    }

    /**
     * @return array{int, int, int}
     */
    private function colorAt(string $path, int $x, int $y): array
    {
        $color = imagecolorat($this->open($path), $x, $y);

        return [($color >> 16) & 0xFF, ($color >> 8) & 0xFF, $color & 0xFF];
    }

    private function isTransparentAt(string $path, int $x, int $y): bool
    {
        // The alpha channel of GD runs from 0 (opaque) to 127 (fully transparent).
        return ((imagecolorat($this->open($path), $x, $y) >> 24) & 0x7F) === 127;
    }

    private function open(string $path): \GdImage
    {
        $image = str_ends_with($path, '.png') ? imagecreatefrompng($path) : imagecreatefromjpeg($path);
        self::assertNotFalse($image, sprintf('"%s" could not be read back', $path));

        return $image;
    }
}
