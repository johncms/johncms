<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Auth\Infrastructure\Persistence\Repository;

use Johncms\Auth\Events\AuthEvent;
use Johncms\Auth\Events\AuthEventRepositoryInterface;

final class EloquentAuthEventRepository implements AuthEventRepositoryInterface
{
    public function store(array $attributes): void
    {
        AuthEvent::query()->create($attributes);
    }

    public function deleteBefore(int $timestamp): int
    {
        return AuthEvent::query()->where('created_at', '<', $timestamp)->delete();
    }
}
