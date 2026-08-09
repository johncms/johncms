<?php

declare(strict_types=1);

namespace Johncms\View;

/**
 * The way the application renders a template, whichever engine is behind it.
 *
 * Callers name a template and hand over data; nothing else about the engine is part of the
 * contract, so a caller never depends on Twig itself.
 */
interface RendererInterface
{
    /**
     * @param array<string, mixed> $data
     */
    public function render(string $template, array $data = []): string;

    public function exists(string $template): bool;
}
