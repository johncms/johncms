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
                LEFT JOIN `forum_read` ON `forum_topic`.`id` = `forum_read`.`topic_id` AND `forum_read`.`user_id` = '" . $this->user->id . "'
                WHERE (`forum_read`.`topic_id` IS NULL OR `forum_topic`.`last_post_date` > `forum_read`.`time`)
                " . ($this->user->hasAnyRole() ? '' : ' AND (`forum_topic`.`deleted` != 1 OR `forum_topic`.`deleted` IS NULL)')
            );
            return $total->cnt;
        }
        return 0;
    }
}
