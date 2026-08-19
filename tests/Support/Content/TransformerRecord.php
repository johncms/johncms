<?php

declare(strict_types=1);

namespace Tests\Support\Content;

use Johncms\Content\ContentContext;

/**
 * What the steps of a pipeline saw while it ran, shared by every RecordingContentTransformer of
 * one test.
 */
final class TransformerRecord
{
    /** @var list<string> The steps that ran, in the order they ran. */
    public array $names = [];

    /** The HTML the last step was handed. */
    public ?string $html = null;

    public ?ContentContext $context = null;
}
