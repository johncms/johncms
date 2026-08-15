<?php

declare(strict_types=1);

namespace Johncms\View\Twig\Runtime;

use Illuminate\Database\Capsule\Manager;
use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\Authorization\CorePermissions;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\RuntimeExtensionInterface;

/**
 * The figures of the developer panel in the footer: how many queries the page ran, how much
 * memory it took and how long it took.
 *
 * Whether the panel is shown at all is decided here rather than in the template, so the query log
 * is never read on a page that does not print it.
 */
final readonly class DebugPanelRuntime implements RuntimeExtensionInterface
{
    public function __construct(
        private AccessCheckerInterface $accessChecker,
        private RequestStack $requestStack,
    ) {
    }

    /**
     * @return null|array{queries: array<array{query: string, bindings: array<mixed>, time: float}>, memory: string, time: float}
     */
    public function stats(): ?array
    {
        if (! $this->isVisible()) {
            return null;
        }

        return [
            'queries' => Manager::connection()->getQueryLog(),
            'memory'  => format_size(memory_get_usage()),
            'time'    => round(microtime(true) - $this->startedAt(), 2),
        ];
    }

    private function isVisible(): bool
    {
        return DEBUG_FOR_ALL || (DEBUG && $this->accessChecker->allows(CorePermissions::SYSTEM_DEBUG_VIEW));
    }

    /**
     * When the request being served started. Taken from the request rather than from a constant
     * of the process: under a long-running runtime the process is older than the page.
     */
    private function startedAt(): float
    {
        $request = $this->requestStack->getCurrentRequest();

        return (float) ($request?->server->get('REQUEST_TIME_FLOAT') ?? microtime(true));
    }
}
