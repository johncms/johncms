<?php

declare(strict_types=1);

namespace Johncms\Forum;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Johncms\Online\Models\GuestSession;
use Johncms\Online\OnlineCounter;
use Johncms\Users\User;

class ForumCounters
{
    private ?User $user;
    private OnlineCounter $onlineCounter;

    public function __construct(?User $user, OnlineCounter $onlineCounter)
    {
        $this->user = $user;
        $this->onlineCounter = $onlineCounter;
    }

    public function onlineUsers(bool $forCurrentPage = false): int
    {
        if ($forCurrentPage) {
            $route = di('route');
            $params = $route->getVars();
            $routeParams = [];
            if (array_key_exists('id', $params)) {
                $routeParams['id'] = $params['id'];
            }
            return $this->onlineCounter->getUsersForRoute($route->getName(), $routeParams);
        }
        return $this->onlineCounter->getUsersForRoute('forum', compareType: OnlineCounter::COMPARE_STARTS_WITH);
    }

    public function onlineGuests(bool $forCurrentPage = false): int
    {
        if ($forCurrentPage) {
            $route = di('route');
            $params = $route->getVars();
            $routeParams = [];
            if (array_key_exists('id', $params)) {
                $routeParams['id'] = $params['id'];
            }
            return $this->onlineCounter->getGuestsForRoute($route->getName(), $routeParams);
        }
        return $this->onlineCounter->getGuestsForRoute('forum', compareType: OnlineCounter::COMPARE_STARTS_WITH);
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
