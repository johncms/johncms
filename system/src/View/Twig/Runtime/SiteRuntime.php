<?php

declare(strict_types=1);

namespace Johncms\View\Twig\Runtime;

use Johncms\Ads;
use Johncms\Counters;
use Johncms\NavChain;
use Twig\Extension\RuntimeExtensionInterface;
use Twig\Markup;

/**
 * The facts the chrome of a page is built from: the navigation chain, the sidebar counters, the
 * notifications, the advertisement blocks and the analytics snippets.
 *
 * They used to be fetched by the layout itself, through di() and the container — four services
 * and a handful of queries on every page, whether the layout printed them or not. Here they are
 * behind a runtime, so a page that renders no sidebar runs no counter query.
 */
final class SiteRuntime implements RuntimeExtensionInterface
{
    /** @var array<string, array<Markup>>|null */
    private ?array $ads = null;

    public function __construct(
        private readonly Counters $counters,
        private readonly Ads $adsService,
        private readonly NavChain $navChain,
    ) {
    }

    /**
     * @return array<array{name?: string, url?: string, active?: bool}>
     */
    public function breadcrumbs(): array
    {
        return $this->navChain->getAll();
    }

    /**
     * @return array<string, int>
     */
    public function notifications(): array
    {
        return $this->counters->notifications();
    }

    /**
     * Everything the main menu shows a badge for, in one call: the menu prints all of them or
     * none, so splitting them into a function each would only multiply the round trips.
     *
     * @return array<string, array<string, int>>
     */
    public function menuCounters(): array
    {
        return [
            'news'      => $this->counters->news(),
            'forum'     => $this->counters->forumCounters(),
            'guestbook' => $this->counters->guestbookCounters(),
            'downloads' => $this->counters->downloadsCounters(),
            'library'   => $this->counters->libraryCounters(),
            'users'     => $this->counters->usersCounters(),
            'album'     => $this->counters->albumCounters(),
        ];
    }

    /**
     * Visitors online, as "users / guests".
     */
    public function online(): string
    {
        return $this->counters->online();
    }

    /**
     * The blocks of one advertisement place. Their content is HTML written by an administrator,
     * so it is handed over as markup.
     *
     * @return array<Markup>
     */
    public function ads(string $place): array
    {
        if ($this->ads === null) {
            $this->ads = array_map(
                static fn (array $blocks): array => array_map(
                    static fn (string $block): Markup => new Markup($block, 'UTF-8'),
                    $blocks
                ),
                $this->adsService->getAds()
            );
        }

        return $this->ads[$place] ?? [];
    }

    /**
     * The snippets of the enabled analytics systems — external scripts and counter images.
     *
     * @return array<Markup>
     */
    public function analytics(): array
    {
        return array_map(
            static fn (string $counter): Markup => new Markup($counter, 'UTF-8'),
            $this->counters->counters()
        );
    }
}
