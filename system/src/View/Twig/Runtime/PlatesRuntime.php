<?php

declare(strict_types=1);

namespace Johncms\View\Twig\Runtime;

use Johncms\View\PlatesRenderer;
use Twig\Extension\RuntimeExtensionInterface;
use Twig\Markup;

final readonly class PlatesRuntime implements RuntimeExtensionInterface
{
    public function __construct(private PlatesRenderer $plates)
    {
    }

    /**
     * A Plates template returns finished markup, so it is wrapped in Markup: escaping it would
     * print its tags instead of rendering them.
     *
     * @param array<string, mixed> $data
     */
    public function render(string $template, array $data = []): Markup
    {
        return new Markup($this->plates->render($template, $data), 'UTF-8');
    }
}
