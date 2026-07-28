<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Users;

use Johncms\Http\Environment;
use Johncms\Http\Request;

class UserFactory
{
    public function __construct(
        private readonly Environment $env,
    ) {
    }

    /**
     * Builds the shared instance holding the current user: a guest, since the request it belongs
     * to is not known at container build time. It is authenticate() that loads the visitor into
     * it, once per request.
     */
    public function __invoke(): User
    {
        return new User();
    }

    /**
     * Identifies the visitor of the given request by their cookies and loads them into the shared
     * current-user instance.
     */
    public function authenticate(User $currentUser, Request $request): void
    {
        $this->hydrate($currentUser, $this->resolveUser($request));
    }

    protected function resolveUser(Request $request): User
    {
        $userId = $request->cookies->getInt('cuid', 0);

        if ($userId !== 0) {
            return $this->authentication($userId, md5($request->cookies->getString('cups', '')));
        }

        return new User();
    }

    /**
     * Replaces the state of the shared instance with the one of the visitor just identified.
     *
     * The instance itself must survive: the constructors of the controllers and of the services
     * built from them hold a reference to it, so handing out a new object would leave them with
     * the user of the request the container was built for.
     */
    private function hydrate(User $currentUser, User $visitor): void
    {
        $currentUser->setRawAttributes($visitor->getAttributes(), true);
        $currentUser->exists = $visitor->exists;
        // Anything loaded for the previous visitor (the ip history, the notifications) belongs to
        // them, and Eloquent would keep serving it from here as if it were the current user's.
        $currentUser->setRelations([]);
    }

    private function authentication(int $userId, string $userPassword): User
    {
        $user = User::query()->find($userId);

        if ($user instanceof User) {
            if ($userPassword === $user->password && $this->checkPermit($user)) {
                $this->ipHistory($user); // Фиксируем историю IP
                return $user;
            }
            // Если авторизация не прошла
            ++$user->failed_login;
            $user->save();
            $this->userUnset();
        } else {
            // Если пользователь не существует
            $this->userUnset();
        }

        return new User();
    }

    private function checkPermit(User $user): bool
    {
        return $user->failed_login < 3
            || ($user->failed_login > 2
                && $user->ip === $this->env->getIp(false)
                && $user->browser === $this->env->getUserAgent());
    }

    /**
     * Фиксация истории IP адресов пользователя
     *
     * @param User $user
     * @return void
     */
    protected function ipHistory(User $user): void
    {
        $ip_via_proxy = $this->env->getIpViaProxy(false);
        $ip_via_proxy = empty($ip_via_proxy) ? '' : $ip_via_proxy;

        if ($user->ip_via_proxy !== $ip_via_proxy || $user->ip !== $this->env->getIp(false)) {
            // Удаляем из истории текущий адрес (если есть)
            $ip_history = $user->ipHistory();
            $ip_history->where('ip', '=', $this->env->getIp())
                ->where('ip_via_proxy', '=', $this->env->getIpViaProxy())
                ->delete();

            // Вставляем в историю предыдущий адрес IP
            $ip_history->create(
                [
                    'user_id'      => $user->id,
                    'ip'           => ip2long($user->ip),
                    'ip_via_proxy' => ip2long($user->ip_via_proxy),
                    'time'         => $user->lastdate,
                ]
            );

            // Обновляем текущий адрес в таблице `users`
            $user->ip = $this->env->getIp(false);
            $user->ip_via_proxy = empty($ip_via_proxy) ? 0 : $ip_via_proxy;
            $user->save();
        }
    }

    /**
     * Уничтожаем данные авторизации юзера
     *
     * @return void
     */
    protected function userUnset(): void
    {
        setcookie('cuid', '');
        setcookie('cups', '');
    }
}
