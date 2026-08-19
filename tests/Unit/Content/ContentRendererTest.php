<?php

declare(strict_types=1);

namespace Tests\Unit\Content;

use Dom\HTMLDocument;
use Johncms\Content\ContentContext;
use Johncms\Content\ContentRenderer;
use Johncms\Content\Html\HtmlFragment;
use Johncms\Content\Transformer\ContentTransformerInterface;
use Johncms\Content\Transformer\ContentTransformerRegistry;
use Johncms\Security\HtmlPolicy;
use Johncms\Security\HtmlSanitizerInterface;
use PHPUnit\Framework\TestCase;
use Tests\Support\Content\RecordingContentTransformer;
use Tests\Support\Content\TransformerRecord;

final class ContentRendererTest extends TestCase
{
    public function testTheStepsRunInTheOrderOfTheirPriority(): void
    {
        $record = new TransformerRecord();
        $this->renderer([
            new RecordingContentTransformer($record, priority: 10, name: 'ten'),
            new RecordingContentTransformer($record, priority: 100, name: 'hundred'),
            new RecordingContentTransformer($record, priority: -5, name: 'minus'),
        ])->render('<p>text</p>');

        self::assertSame(['hundred', 'ten', 'minus'], $record->names);
    }

    public function testTheTextIsSanitizedBeforeTheStepsSeeIt(): void
    {
        $record = new TransformerRecord();
        $this->renderer([new RecordingContentTransformer($record)])
            ->render('<p>keep</p><script>drop()</script>');

        self::assertSame('<p>keep</p>', $record->html);
    }

    public function testTheContextReachesEveryStep(): void
    {
        $record = new TransformerRecord();
        $context = new ContentContext(HtmlPolicy::Inline, adminSmilies: true);

        $this->renderer([new RecordingContentTransformer($record)])->render('<p>text</p>', $context);

        self::assertSame($context, $record->context);
    }

    public function testAnEmptyTextNeverReachesThePipeline(): void
    {
        $record = new TransformerRecord();
        $renderer = $this->renderer([new RecordingContentTransformer($record)]);

        self::assertSame('', (string) $renderer->render(''));
        self::assertSame('', (string) $renderer->render('   '));
        self::assertSame([], $record->names);
    }

    /**
     * Markup is an object and an object is truthy however empty it is, so a template cannot ask
     * "is there a text?" about it. A source that can have nothing to show answers with null.
     */
    public function testRenderOrNullAnswersWithNullWhenNothingIsLeft(): void
    {
        $renderer = $this->renderer([]);

        self::assertNull($renderer->renderOrNull(''));
        self::assertNotNull($renderer->renderOrNull('<p>text</p>'));
    }

    public function testPlainTextIsTheRenderedContentStrippedOfItsMarkup(): void
    {
        self::assertSame('Hello world', $this->renderer([])->toPlainText("<p>Hello</p>\n  <p>world</p>"));
    }

    /**
     * @param list<ContentTransformerInterface> $transformers
     */
    private function renderer(array $transformers): ContentRenderer
    {
        return new ContentRenderer(
            $this->sanitizer(),
            new ContentTransformerRegistry($transformers),
            new HtmlFragment()
        );
    }

    /**
     * Strips what carries behaviour and nothing else: the pipeline is what is under test here,
     * not the policy of the sanitizer.
     */
    private function sanitizer(): HtmlSanitizerInterface
    {
        return new class implements HtmlSanitizerInterface {
            public function sanitize(string $html, HtmlPolicy|string $policy = HtmlPolicy::RichContent): string
            {
                return (string) preg_replace('#<script[^>]*>.*?</script>#is', '', $html);
            }

            public function toPlainText(string $html, HtmlPolicy|string $policy = HtmlPolicy::RichContent): string
            {
                return trim(strip_tags($this->sanitize($html, $policy)));
            }
        };
    }
}
