<?php

declare(strict_types=1);

namespace Johncms\View\Twig\Extension;

use Johncms\View\Twig\Runtime\AdminRuntime;
use Johncms\View\Twig\Runtime\DebugPanelRuntime;
use Johncms\View\Twig\Runtime\SiteRuntime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * What the chrome of a page is built from. Everything here is lazy: the services behind these
 * functions are built by the container only when a template calls one.
 */
final class SiteExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('breadcrumbs', [SiteRuntime::class, 'breadcrumbs']),
            new TwigFunction('notifications', [SiteRuntime::class, 'notifications']),
            new TwigFunction('menu_counters', [SiteRuntime::class, 'menuCounters']),
            new TwigFunction('online', [SiteRuntime::class, 'online']),
            new TwigFunction('ads', [SiteRuntime::class, 'ads']),
            new TwigFunction('analytics', [SiteRuntime::class, 'analytics']),
            new TwigFunction('admin_counters', [AdminRuntime::class, 'counters']),
            new TwigFunction('pending_migrations', [AdminRuntime::class, 'pendingMigrations']),
            new TwigFunction('debug_stats', [DebugPanelRuntime::class, 'stats']),
        ];
    }
}
