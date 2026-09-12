<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Infrastructure\Persistence\Repository;

use Johncms\Modules\Forum\Domain\Repository\ForumAdminRepositoryInterface;
use Johncms\Modules\Forum\Domain\Models\ForumFile;
use Johncms\Modules\Forum\Domain\Models\ForumMessage;
use Johncms\Modules\Forum\Domain\Models\ForumSection;
use Johncms\Modules\Forum\Domain\Models\ForumTopic;
use Johncms\Modules\Forum\Domain\Models\ForumVote;

final class ForumAdminRepository implements ForumAdminRepositoryInterface
{
    public function dashboardCounters(): array
    {
        return [
            'total_cat'     => ForumSection::query()
                ->where('section_type', '!=', 1)
                ->orWhereNull('section_type')
                ->count(),
            'total_sub'     => ForumSection::query()->where('section_type', 1)->count(),
            'total_thm'     => ForumTopic::query()->count(),
            'total_thm_del' => ForumTopic::query()->where('deleted', 1)->count(),
            'total_msg'     => ForumMessage::query()->count(),
            'total_msg_del' => ForumMessage::query()->where('deleted', 1)->count(),
            'total_files'   => ForumFile::query()->count(),
            'total_votes'   => ForumVote::query()->where('type', 1)->count(),
        ];
    }
}
