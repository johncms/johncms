<?php

declare(strict_types=1);

namespace Johncms\View;

use InvalidArgumentException;
use RuntimeException;

/**
 * Routes a template to the engine that owns it, by the shape of its name.
 *
 * Twig names a namespace with a leading @ (`@news/index.twig`), Plates separates it with ::
 * (`news::index`), so the two vocabularies never collide and no registry, flag or per-module
 * switch is needed: a single page can move to Twig while the rest of its module stays behind.
 */
final readonly class DelegatingRenderer implements RendererInterface
{
    public function __construct(
        private RendererInterface $platesRenderer,
        private ?RendererInterface $twigRenderer = null,
    ) {
    }

    public function render(string $template, array $data = []): string
    {
        return $this->rendererFor($template)->render($template, $data);
    }

    public function exists(string $template): bool
    {
        return $this->rendererFor($template)->exists($template);
    }

    private function rendererFor(string $template): RendererInterface
    {
        if (str_starts_with($template, '@')) {
            if ($this->twigRenderer === null) {
                throw new RuntimeException(
                    sprintf('The template "%s" needs the Twig renderer, which is not registered.', $template)
                );
            }

            return $this->twigRenderer;
        }

        if (str_contains($template, '::')) {
            return $this->platesRenderer;
        }

        throw new InvalidArgumentException(
            sprintf(
                'The template name "%s" names no engine. Use "@namespace/file.twig" for Twig or "namespace::file" for Plates.',
                $template
            )
        );
    }
}
