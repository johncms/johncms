<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Auth\External;

use Illuminate\Support\Collection;

interface UserIdentityRepositoryInterface
{
    public function findByProviderUser(string $provider, string $providerUserId): ?UserIdentity;

    public function findForUser(int $userId, string $provider): ?UserIdentity;

    /**
     * @return Collection<int, UserIdentity>
     */
    public function allForUser(int $userId): Collection;

    /**
     * @param array<string, mixed> $attributes
     */
    public function create(array $attributes): UserIdentity;

    public function touchLogin(int $id, int $timestamp): void;

    public function delete(int $id): void;

    /**
     * How many accounts sign in through this provider, and how many of them have no other way in.
     * Asked before a provider is switched off: doing that silently locks people out.
     *
     * @return array{users: int, without_alternative: int}
     */
    public function usageOf(string $provider): array;
}
