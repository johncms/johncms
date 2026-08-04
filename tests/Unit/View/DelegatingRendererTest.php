<?php

declare(strict_types=1);

namespace Tests\Unit\View;

use InvalidArgumentException;
use Johncms\View\DelegatingRenderer;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * The seam that lets both engines serve pages while the Twig migration runs.
 *
 * Everything hangs on the two template vocabularies never colliding: whichever engine a page
 * belongs to is readable from its name alone, so a single template moves to Twig without its
 * module, a flag or a line of configuration moving with it.
 */
final class DelegatingRendererTest extends TestCase
{
    public function testATwigNameGoesToTheTwigRenderer(): void
    {
        $twig = new RecordingRenderer('twig output');
        $renderer = new DelegatingRenderer(new RecordingRenderer('plates output'), $twig);

        self::assertSame('twig output', $renderer->render('@news/index.twig', ['a' => 1]));
        self::assertSame([['@news/index.twig', ['a' => 1]]], $twig->calls);
    }

    public function testAPlatesNameGoesToThePlatesRenderer(): void
    {
        $plates = new RecordingRenderer('plates output');
        $renderer = new DelegatingRenderer($plates, new RecordingRenderer('twig output'));

        self::assertSame('plates output', $renderer->render('news::index', ['a' => 1]));
        self::assertSame([['news::index', ['a' => 1]]], $plates->calls);
    }

    public function testExistsIsDispatchedTheSameWay(): void
    {
        $plates = new RecordingRenderer();
        $twig = new RecordingRenderer();
        $renderer = new DelegatingRenderer($plates, $twig);

        $renderer->exists('@news/index.twig');
        $renderer->exists('news::index');

        self::assertSame([['@news/index.twig', []]], $twig->calls);
        self::assertSame([['news::index', []]], $plates->calls);
    }

    /**
     * Until the Twig environment is built, the container registers the dispatcher without it.
     * A Twig name then has to fail loudly rather than fall through to Plates, which would look
     * for a namespace named "@news/index" and report a missing template instead.
     */
    public function testATwigNameWithoutATwigRendererFails(): void
    {
        $renderer = new DelegatingRenderer(new RecordingRenderer());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('needs the Twig renderer');

        $renderer->render('@news/index.twig');
    }

    public function testANameThatBelongsToNoEngineIsRejected(): void
    {
        $renderer = new DelegatingRenderer(new RecordingRenderer(), new RecordingRenderer());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('names no engine');

        $renderer->render('news/index');
    }
}
