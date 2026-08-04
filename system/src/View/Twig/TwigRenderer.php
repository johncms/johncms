<?php

declare(strict_types=1);

namespace Johncms\View\Twig;

use Johncms\View\RendererInterface;
use Twig\Environment;

final readonly class TwigRenderer implements RendererInterface
{
    public function __construct(private Environment $twig)
    {
    }

    public function render(string $template, array $data = []): string
    {
        return $this->twig->render($template, $data);
    }

    public function exists(string $template): bool
    {
        return $this->twig->getLoader()->exists($template);
    }
}
