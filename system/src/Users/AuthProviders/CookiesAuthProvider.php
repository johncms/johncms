<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Users\AuthProviders;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Johncms\Http\Request;
use Johncms\Users\StoredAuth;
use Johncms\Users\User;

class CookiesAuthProvider implements AuthProviderInterface
{
    public const COOKIE_TOKEN_FIELD = 'johncms_auth_token';
    public const COOKIE_USER_FIELD = 'johncms_auth_user';
    protected ?int $userId;
    protected ?string $token;

    public function __construct(public Request $request, public SessionAuthProvider $sessionAuthProvider)
    {
        $this->userId = $this->request->getCookie(self::COOKIE_USER_FIELD, null, FILTER_VALIDATE_INT);
        $this->token = $this->request->getCookie(self::COOKIE_TOKEN_FIELD);
    }

    public function authenticate(): ?User
    {
        if (empty($this->userId) || empty($this->token)) {
            return null;
        }

        /** @var User|null $user */
        $user = (new User())
            ->where('id', $this->userId)
            ->whereHas('storedAuth', fn(Builder $builder) => $builder->where('token', $this->token))
            ->first();
        if ($user === null) {
            $this->forget();
            return null;
        } else {
            $this->sessionAuthProvider->store($user);
            (new StoredAuth())->where('user_id', $user->id)->where('token', $this->token)->first()->touch();
        }

        return $user;
    }

    public function store(User $user): void
    {
        $token = Str::random(100);
        if (! headers_sent()) {
            $cookieParams = [
                'expires' => time() + (86400 * 365 * 5),
                'path'    => '/',
            ];
            setcookie(self::COOKIE_USER_FIELD, (string) $user->id, $cookieParams);
            setcookie(self::COOKIE_TOKEN_FIELD, $token, $cookieParams);
        }

        (new StoredAuth())->create(
            [
                'user_id' => $user->id,
                'token' => $token,
                'ip' => $this->request->getIp(),
                'user_agent' => $this->request->getUserAgent(),
            ]
        );
    }

    public function forget(): void
    {
        if (! headers_sent()) {
            $cookieParams = [
                'expires' => time() - 86400,
                'path'    => '/',
            ];
            setcookie(self::COOKIE_USER_FIELD, '', $cookieParams);
            setcookie(self::COOKIE_TOKEN_FIELD, '', $cookieParams);
        }
        if ($this->userId && $this->token) {
            (new StoredAuth())->where('user_id', $this->userId)->where('token', $this->token)->delete();
        }
    }

    public function forgetAll(User $user): void
    {
        (new StoredAuth())->where('user_id', $user->id)->delete();
    }
}
