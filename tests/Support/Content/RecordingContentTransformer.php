<?php

declare(strict_types=1);

namespace Tests\Support\Content;

use Dom\HTMLDocument;
use Johncms\Content\ContentContext;
use Johncms\Content\Transformer\ContentTransformerInterface;

/**
 * A step of the content pipeline that changes nothing and writes down that it ran.
 */
final readonly class RecordingContentTransformer implements ContentTransformerInterface
{
    public function __construct(
        private TransformerRecord $record,
        private int $priority = 0,
        private string $name = 'step',
    ) {
    }

    public function priority(): int
    {
        return $this->priority;
    }

    public function transform(HTMLDocument $document, ContentContext $context): void
    {
        $this->record->names[] = $this->name;
        $this->record->html = $document->body->innerHTML;
        $this->record->context = $context;
    }
}
