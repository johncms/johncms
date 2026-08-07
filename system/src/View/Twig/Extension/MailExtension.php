<?php

declare(strict_types=1);

namespace Johncms\View\Twig\Extension;

use Johncms\View\Twig\Runtime\MailRuntime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * The functions of the mail environment. There is no request behind a message, so nothing of the
 * visitor, the csrf token or the build assets is offered here.
 */
final class MailExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('asset', [MailRuntime::class, 'asset']),
            new TwigFunction('home_url', [MailRuntime::class, 'homeUrl']),
            new TwigFunction('copyright', [MailRuntime::class, 'copyright']),
        ];
    }
}
