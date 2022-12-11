<?php

declare(strict_types=1);

namespace Johncms\Forum\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Johncms\Http\Request;
use Johncms\Online\Models\GuestSession;
use Johncms\Online\OnlineCounter;
use Johncms\Online\Resources\GuestResource;
use Johncms\Online\Resources\UserResource;
use Johncms\Users\User;

class OnlineController extends BaseForumController
{
    public function allUsers(OnlineCounter $onlineCounter, Request $request): string
    {
        $users = User::query()
            ->with('activity')
            ->online()
            ->whereHas('activity', function (Builder $builder) use ($onlineCounter, $request) {
                $id = $request->getQuery('id', 0, FILTER_VALIDATE_INT);
                if ($id) {
                    return $onlineCounter->buildCondition($builder, $request->getQuery('topic') ? 'forum.topic' : 'forum.section', ['id' => $id]);
                } else {
                    return $onlineCounter->buildCondition($builder, 'forum.', [], OnlineCounter::COMPARE_STARTS_WITH);
                }
            })
            ->paginate();
        $userResource = UserResource::createFromCollection($users);
        $route = di('route');
        return $this->render->render(
            'johncms/forum::online/users',
            [
                'data' => [
                    'users'      => $userResource->getItems(),
                    'pagination' => $users->render(),
                    'total'      => $users->total(),
                    'tabs'       => [
                        'users'  => [
                            'name'   => __('Users'),
                            'url'    => route('forum.onlineUsers', [], $request->getQueryParams()),
                            'active' => ($route->getName() === 'forum.onlineUsers'),
                        ],
                        'guests' => [
                            'name'   => __('Guests'),
                            'url'    => route('forum.onlineGuests', [], $request->getQueryParams()),
                            'active' => ($route->getName() === 'forum.onlineGuests'),
                        ],
                    ],
                ],
            ]
        );
    }

    public function allGuests(OnlineCounter $onlineCounter, Request $request): string
    {
        $guests = GuestSession::query()
            ->online()
            ->where(function (Builder $builder) use ($onlineCounter, $request) {
                $id = $request->getQuery('id', 0, FILTER_VALIDATE_INT);
                if ($id) {
                    return $onlineCounter->buildCondition($builder, $request->getQuery('topic') ? 'forum.topic' : 'forum.section', ['id' => $id]);
                } else {
                    return $onlineCounter->buildCondition($builder, 'forum.', [], OnlineCounter::COMPARE_STARTS_WITH);
                }
            })
            ->paginate();
        $userResource = GuestResource::createFromCollection($guests);
        $route = di('route');
        return $this->render->render('johncms/forum::online/users', [
            'data' => [
                'users'      => $userResource->getItems(),
                'pagination' => $guests->render(),
                'total'      => $guests->total(),
                'tabs'       => [
                    'users'  => [
                        'name'   => __('Users'),
                        'url'    => route('forum.onlineUsers', [], $request->getQueryParams()),
                        'active' => ($route->getName() === 'forum.onlineUsers'),
                    ],
                    'guests' => [
                        'name'   => __('Guests'),
                        'url'    => route('forum.onlineGuests', [], $request->getQueryParams()),
                        'active' => ($route->getName() === 'forum.onlineGuests'),
                    ],
                ],
            ],
        ]);
    }
}
