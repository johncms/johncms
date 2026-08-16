<?php

declare(strict_types=1);

namespace Johncms\View\Twig\Extension;

use Johncms\View\Twig\Runtime\AuthRuntime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * What a template is allowed to show. Lazy through the runtime, so a page that asks nothing
 * about permissions resolves no roles.
 */
final class AuthExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('can', [AuthRuntime::class, 'can']),
            new TwigFunction('impersonation', [AuthRuntime::class, 'impersonation']),
            new TwigFunction('external_providers', [AuthRuntime::class, 'externalProviders']),
        ];
    }
}
