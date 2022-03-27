<?php

declare(strict_types=1);

namespace Johncms\Forum;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Johncms\Online\Models\GuestSession;
use Johncms\Users\User;

class ForumCounters
{
    private ?User $user;

    public function __construct(?User $user)
    {
        $this->user = $user;
    }

    public function onlineUsers(): int
    {
        return (new User())->online()->whereHas('activity', function (Builder $builder) {
            return $builder->where('route', 'like', 'forum.%');
        })->count();
    }

    public function onlineGuests(): int
    {
        return (new GuestSession())->online()->where('route', 'like', 'forum.%')->count();
    }

    public function unreadMessages(): int
    {
        if ($this->user) {
            $total = DB::selectOne(
                "SELECT COUNT(*) as cnt FROM `forum_topic`
                LEFT JOIN `cms_forum_rdm` ON `forum_topic`.`id` = `cms_forum_rdm`.`topic_id` AND `cms_forum_rdm`.`user_id` = '" . $this->user->id . "'
                WHERE (`cms_forum_rdm`.`topic_id` IS NULL OR `forum_topic`.`last_post_date` > `cms_forum_rdm`.`time`)
                " . ($this->user->hasAnyRole() >= 7 ? '' : ' AND (`forum_topic`.`deleted` != 1 OR `forum_topic`.`deleted` IS NULL)')
            );
            return $total->cnt;
        }
        return 0;
    }
}
