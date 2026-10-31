<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\Dashboard;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Modules\Downloads\Application\Services\DownloadsPermissions;
use Johncms\Modules\Downloads\Domain\Repository\DownloadFileRepositoryInterface;
use Johncms\View\Dashboard\AttentionItem;
use Johncms\View\Dashboard\AttentionLevel;
use Johncms\View\Dashboard\DashboardAttentionProviderInterface;

/**
 * The files waiting for moderation, for the moderator of the downloads.
 */
final readonly class ModerationAttentionProvider implements DashboardAttentionProviderInterface
{
    public function __construct(
        private AccessCheckerInterface $accessChecker,
        private DownloadFileRepositoryInterface $files,
    ) {
    }

    public function attentionItems(): iterable
    {
        if (! $this->accessChecker->allows(DownloadsPermissions::MODERATE)) {
            return;
        }

        $count = $this->files->countAwaitingModeration();

        if ($count > 0) {
            yield new AttentionItem(d__('downloads', 'Downloads on moderation'), '/downloads/moderation', AttentionLevel::Pending, $count, 'download');
        }
    }
}
