<?php

declare(strict_types=1);

namespace Johncms\View\Twig;

/**
 * The templates of the installer, which lives under the document root rather than in a module and
 * is deleted once the site is up. A theme may override any of them the usual way, by mirroring
 * the path under templates/install.
 */
final readonly class InstallTemplatePaths implements TemplatePathProviderInterface
{
    public function paths(): array
    {
        return ['install' => [PUBLIC_PATH . 'install' . DS . 'templates']];
    }
}
