<?php

declare(strict_types=1);

namespace Johncms\View\Dashboard;

/**
 * How a module tells the dashboard of the panel that something of it needs the administrator — a
 * queue of moderation, a problem of the server — without the dashboard knowing the module exists.
 *
 * A module implements this, the container tags it, and the dashboard lists what the switched-on
 * modules yield, the most pressing first. A module that is switched off has no provider.
 */
interface DashboardAttentionProviderInterface
{
    /**
     * What needs the current visitor, who may open the panel whenever this is asked. Nothing when
     * nothing does; a provider checks the permission its line needs itself.
     *
     * @return iterable<AttentionItem>
     */
    public function attentionItems(): iterable;
}
