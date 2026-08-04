<?php

declare(strict_types=1);

namespace Tests\Unit\View;

use Johncms\View\RendererInterface;

/**
 * A renderer that records what it was asked for. Lets the dispatcher tests assert on which
 * engine received a template, which is the whole of their subject.
 */
final class RecordingRenderer implements RendererInterface
{
    /** @var array<array{string, array<string, mixed>}> */
    public array $calls = [];

    public function __construct(private readonly string $output = '')
    {
    }

    public function render(string $template, array $data = []): string
    {
        $this->calls[] = [$template, $data];

        return $this->output;
    }

    public function exists(string $template): bool
    {
        $this->calls[] = [$template, []];

        return true;
    }
}
