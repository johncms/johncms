<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\Access;

use Johncms\Auth\CurrentUser;
use Johncms\Modules\Downloads\Domain\Models\DownloadCategory;

final readonly class DownloadsAccess
{
    public function __construct(private CurrentUser $currentUser)
    {
    }

    /**
     * Whether a member may upload into a folder: one that takes the files of the members, by an
     * account the whole site is not closed to. The downloads have no ban of their own.
     */
    public function mayUpload(DownloadCategory $category): bool
    {
        return (bool) $category->field
            && $this->currentUser->isValid()
            && ! isset($this->currentUser->user()->ban['1']);
    }
}
