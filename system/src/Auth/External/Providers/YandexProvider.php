<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Auth\External\Providers;

use Johncms\Auth\External\AbstractOAuth2Provider;
use Johncms\Auth\External\ExternalIdentityDTO;

final class YandexProvider extends AbstractOAuth2Provider
{
    public function key(): string
    {
        return 'yandex';
    }

    public function label(): string
    {
        return 'Yandex';
    }

    protected function authorizeUrl(): string
    {
        return 'https://oauth.yandex.ru/authorize';
    }

    protected function tokenUrl(): string
    {
        return 'https://oauth.yandex.ru/token';
    }

    protected function userInfoUrl(): string
    {
        return 'https://login.yandex.ru/info?format=json';
    }

    protected function scope(): string
    {
        return 'login:email login:info';
    }

    protected function mapIdentity(array $userInfo, array $token): ExternalIdentityDTO
    {
        $email = $userInfo['default_email'] ?? null;
        $avatarId = (string) ($userInfo['default_avatar_id'] ?? '');

        return new ExternalIdentityDTO(
            providerUserId: (string) ($userInfo['id'] ?? ''),
            email: $email === null ? null : (string) $email,
            // An address of a Yandex account belongs to that account; the service has no notion
            // of an unconfirmed one here.
            emailVerified: $email !== null,
            nickname: isset($userInfo['display_name']) ? (string) $userInfo['display_name'] : null,
            avatarUrl: $avatarId === '' ? null : 'https://avatars.yandex.net/get-yapic/' . $avatarId . '/islands-200',
        );
    }
}
