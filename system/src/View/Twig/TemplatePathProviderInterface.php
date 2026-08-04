<?php

declare(strict_types=1);

namespace Johncms\View\Twig;

/**
 * Extra template paths contributed by a module, for what the naming convention does not cover.
 *
 * Registered with the johncms.template_paths tag. A module that keeps its templates where they
 * are expected needs none of this — its namespace is registered without any configuration.
 */
interface TemplatePathProviderInterface
{
    /**
     * @return array<string, array<string>> Namespace (without the leading @) to directories,
     *                                      most specific first.
     */
    public function paths(): array;
}
