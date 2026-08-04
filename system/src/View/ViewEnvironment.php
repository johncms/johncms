<?php

declare(strict_types=1);

namespace Johncms\View;

/**
 * The environments templates are rendered in.
 *
 * They differ by what is available to a template, not by how the page looks: the admin panel and
 * the public site share one environment and are told apart by their namespaces. Mail has no
 * request, no user and no build assets, and the installer runs before there is a configuration
 * at all — those cannot be served by the same environment as HTTP.
 */
enum ViewEnvironment: string
{
    case Web = 'web';
    case Mail = 'mail';
    case Install = 'install';
}
