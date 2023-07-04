<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

namespace Johncms\Community\Services;

use Illuminate\Support\Facades\DB;
use Johncms\NavChain;
use Johncms\Users\User;
use Johncms\View\MetaTagManager;

class CommunityTopService
{
    protected NavChain $navChain;
    protected MetaTagManager $metaTagManager;

    public function __construct()
    {
        $this->navChain = di(NavChain::class);
        $this->metaTagManager = di(MetaTagManager::class);
    }

    public function forumTop(): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $this->metaTagManager->setAll(__('Most active in Forum'));
        $this->navChain->add(__('Most active in Forum'));

        return User::query()
            ->select('users.*', DB::raw('(select count(*) from `forum_topic` where `users`.`id` = `forum_topic`.`user_id`) as `forum_posts_count`'))
            ->orderBy('forum_posts_count', 'desc')->paginate();
    }

    public function guestbookTop(): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $this->metaTagManager->setAll(__('Most active in Guestbook'));
        $this->navChain->add(__('Most active in Guestbook'));

        return User::query()
            ->select('users.*', DB::raw('(select count(*) from `guest` where `users`.`id` = `guest`.`user_id`) as `guest_message_count`'))
            ->orderBy('guest_message_count', 'desc')->paginate();
    }

    public function commentTop(): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $this->metaTagManager->setAll(__('Most commentators'));
        $this->navChain->add(__('Most commentators'));

        return User::query()
            ->select('users.*', DB::raw('(select count(*) from `news_comments` where `users`.`id` = `news_comments`.`user_id`) as `comment_count`'))
            ->orderBy('comment_count', 'desc')->paginate();
    }

    public function karmaTop(): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $this->metaTagManager->setAll(__('Best Karma'));
        $this->navChain->add(__('Best Karma'));

        // old query
        //$users = (new User())->selectRaw('*, (`karma_plus` - `karma_minus`) as `karma`')->whereRaw('(`karma_plus` - `karma_minus`) > 0')->orderBy('karma', 'desc')->limit(9)->get();

        return User::query()->where('postguest', '>', 0)
            ->orderBy('postguest', 'desc')->paginate();
    }
}
