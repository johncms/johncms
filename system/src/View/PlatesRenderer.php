<?php

declare(strict_types=1);

namespace Johncms\View;

use Johncms\System\View\Render;
use LogicException;
use Mobicms\Render\Template\TemplateName;

/**
 * The RendererInterface implementation backed by the current Plates engine.
 *
 * An adapter and nothing more: it holds no rendering logic of its own, so the engine can be
 * dropped once the last .phtml template is gone without any caller noticing.
 */
final readonly class PlatesRenderer implements RendererInterface
{
    public function __construct(private Render $engine)
    {
    }

    public function render(string $template, array $data = []): string
    {
        return $this->engine->render($template, $data);
    }

    /**
     * Plates has no existence check of its own: resolving the name is the check, and a missing
     * template — or a namespace nobody registered — is a LogicException from the resolver.
     */
    public function exists(string $template): bool
    {
        try {
            (new TemplateName($this->engine, $template))->getPath();
        } catch (LogicException) {
            return false;
        }

        return true;
    }
}
