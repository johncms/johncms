<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Community\Controllers;

use Illuminate\Support\Carbon;
use Johncms\Community\Services\CommunityService;
use Johncms\Community\Services\CommunityTopService;
use Johncms\Http\Request;
use Johncms\Users\User;

class CommunityController extends \Johncms\Controller\BaseController
{
    protected string $moduleName = 'johncms/community';

    public function __construct()
    {
        parent::__construct();
        $this->navChain->add(__('Community'), route('community.index'));
    }

    public function index(): string
    {
        $this->metaTagManager->setTitle(__('Community'))
            ->setPageTitle(__('Community'));

        return $this->render->render('johncms/community::public/index', [
            'userCount'      => User::query()->count(),
            'adminCount'     => User::query()->has('roles')->count(),
            'birthDaysCount' => User::query()->where('confirmed', 1)
                ->whereMonth('birthday', Carbon::now()->format('m'))
                ->whereDay('birthday', Carbon::now()->format('d'))
                ->count(),
        ]);
    }

    public function users(?User $user = null): string
    {
        $this->metaTagManager->setAll(__('List of users'));
        $this->navChain->add(__('List of users'), route('community.users'));

        return $this->render->render('johncms/community::public/users', [
            'users'       => User::query()->with(['activity', 'avatar'])->orderBy('id')->paginate(),
            'currentUser' => $user,
        ]);
    }

    public function administration(?User $user = null): string
    {
        $this->metaTagManager->setAll(__('Administration'));
        $this->navChain->add(__('Administration'), route('community.administration'));

        return $this->render->render('johncms/community::public/users', [
            'users'       => User::query()->with(['activity', 'avatar'])->has('roles')->orderBy('id')->paginate(),
            'currentUser' => $user,
        ]);
    }

    public function birthdays(?User $user = null): string
    {
        $this->metaTagManager->setAll(__('Birthdays'));
        $this->navChain->add(__('Birthdays'), route('community.birthdays'));

        return $this->render->render('johncms/community::public/users', [
            'users'       => User::query()->where('confirmed', 1)
                ->whereMonth('birthday', Carbon::now()->format('m'))
                ->whereDay('birthday', Carbon::now()->format('d'))
                ->orderBy('id')->paginate(),
            'currentUser' => $user,
        ]);
    }

    public function search(Request $request, CommunityService $communityService, ?User $user = null): string
    {
        $this->metaTagManager->setAll(__('User Search'));
        $this->navChain->add(__('User Search'), route('community.search'));

        $data = $communityService->searchUsers($request);

        return $this->render->render('johncms/community::public/search', [
            'currentUser' => $user,
            'users'       => $data['users'],
            'errors'      => $data['errors'],
            'search'      => $data['search'],
        ]);
    }

    public function top(CommunityTopService $communityTopService, ?User $user = null, string $type = 'forum'): string
    {
        $users = match ($type) {
            'guest' => $communityTopService->guestbookTop(),
            'karma' => $communityTopService->karmaTop(),
            'comm' => $communityTopService->commentTop(),
            default => $communityTopService->forumTop(),
        };

        return $this->render->render('johncms/community::public/top', [
            'activeTab'   => $type,
            'users'       => $users,
            'currentUser' => $user,
        ]);
    }
}
