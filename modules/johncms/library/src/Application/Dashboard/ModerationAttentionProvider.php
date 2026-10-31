<?php

declare(strict_types=1);

namespace Johncms\Modules\Library\Application\Dashboard;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Modules\Library\Application\Services\LibraryPermissions;
use Johncms\Modules\Library\Domain\Repository\LibraryTextRepositoryInterface;
use Johncms\View\Dashboard\AttentionItem;
use Johncms\View\Dashboard\AttentionLevel;
use Johncms\View\Dashboard\DashboardAttentionProviderInterface;

/**
 * The articles waiting for moderation, for the moderator of the library.
 */
final readonly class ModerationAttentionProvider implements DashboardAttentionProviderInterface
{
    public function __construct(
        private AccessCheckerInterface $accessChecker,
        private LibraryTextRepositoryInterface $texts,
    ) {
    }

    public function attentionItems(): iterable
    {
        if (! $this->accessChecker->allows(LibraryPermissions::MODERATE)) {
            return;
        }

        $count = $this->texts->countAwaitingModeration();

        if ($count > 0) {
            yield new AttentionItem(d__('library', 'Articles on moderation'), '/library/premod', AttentionLevel::Pending, $count, 'library');
        }
    }
}
