<?php

declare(strict_types=1);

namespace Johncms\Online;

use Illuminate\Database\Eloquent\Builder;
use Johncms\Online\Models\GuestSession;
use Johncms\Users\User;

class OnlineCounter
{
    public const COMPARE_STRICT = 1;
    public const COMPARE_CONTAINS = 2;
    public const COMPARE_STARTS_WITH = 3;

    public function getUsers(): int
    {
        return User::query()->online()->count();
    }

    public function getGuests(): int
    {
        return GuestSession::query()->online()->count();
    }

    /**
     * Counting online guests for a specific route and params
     */
    public function getGuestsForRoute(string $routeName, array $routeParams = [], int $compareType = OnlineCounter::COMPARE_STRICT): int
    {
        return GuestSession::query()
            ->online()
            ->where(fn(Builder $builder) => $this->buildCondition($builder, $routeName, $routeParams, $compareType))
            ->count();
    }

    /**
     * Counting online users for a specific route and params
     */
    public function getUsersForRoute(string $routeName, array $routeParams = [], int $compareType = OnlineCounter::COMPARE_STRICT): int
    {
        return User::query()
            ->online()
            ->whereHas('activity', fn(Builder $builder) => $this->buildCondition($builder, $routeName, $routeParams, $compareType))
            ->count();
    }

    public function buildCondition(Builder $builder, string $routeName, array $routeParams = [], int $compareType = OnlineCounter::COMPARE_STRICT): Builder
    {
        switch ($compareType) {
            case self::COMPARE_STRICT:
                $builder->where('route', $routeName);
                break;
            case self::COMPARE_CONTAINS:
                $builder->where('route', 'like', '%' . $routeName . '%');
                break;
            case self::COMPARE_STARTS_WITH:
                $builder->where('route', 'like', $routeName . '%');
                break;
        }

        if (! empty($routeParams)) {
            foreach ($routeParams as $key => $value) {
                $builder->where('route_params->' . $key, $value);
            }
        }

        return $builder;
    }
}
