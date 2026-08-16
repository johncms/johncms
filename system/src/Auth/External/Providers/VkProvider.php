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
use Johncms\Auth\External\ExternalAuthContextDTO;
use Johncms\Auth\External\ExternalAuthException;
use Johncms\Auth\External\ExternalCallbackDTO;
use Johncms\Auth\External\ExternalIdentityDTO;

/**
 * VK, through VK ID — the OAuth 2.1 flow at id.vk.com.
 *
 * The classic endpoints at oauth.vk.com are not an option: an application registered today is
 * redirected to VK ID and the classic token exchange answers "Security Error" with nothing else
 * to go on. Three things differ from the usual exchange here, and all three are mandatory:
 *
 * * PKCE is required rather than optional;
 * * the callback carries a `device_id` that the token request does not work without;
 * * the profile comes from a POST to /oauth2/user_info — the address is not in the token, and
 *   users.get does not return one.
 */
final class VkProvider extends AbstractOAuth2Provider
{
    private const BASE_URL = 'https://id.vk.com';

    public function key(): string
    {
        return 'vk';
    }

    public function label(): string
    {
        return 'VK';
    }

    protected function authorizeUrl(): string
    {
        return self::BASE_URL . '/authorize';
    }

    protected function tokenUrl(): string
    {
        return self::BASE_URL . '/oauth2/auth';
    }

    protected function userInfoUrl(): string
    {
        return self::BASE_URL . '/oauth2/user_info';
    }

    /**
     * Space-separated, as OAuth 2.1 spells it. The comma of the classic VK API is one of the
     * ways VK ID silently ends up without the scopes it was asked for.
     */
    protected function scope(): string
    {
        return 'vkid.personal_info email';
    }

    protected function exchangeCode(
        string $code,
        ExternalAuthContextDTO $context,
        ExternalCallbackDTO $callback,
    ): array {
        $deviceId = $callback->get('device_id');

        if ($deviceId === '') {
            throw new ExternalAuthException(__('The service did not return an account identifier'));
        }

        return $this->request(
            'POST',
            $this->tokenUrl(),
            [
                'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
                'body'    => [
                    'grant_type'    => 'authorization_code',
                    'code'          => $code,
                    'code_verifier' => $context->codeVerifier,
                    'client_id'     => $this->settings()->clientId,
                    'device_id'     => $deviceId,
                    'redirect_uri'  => $context->redirectUri,
                    'state'         => $context->state,
                ],
            ]
        );
    }

    protected function fetchUserInfo(string $accessToken, array $token): array
    {
        $response = $this->request(
            'POST',
            $this->userInfoUrl(),
            [
                'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
                'body'    => [
                    'client_id'    => $this->settings()->clientId,
                    'access_token' => $accessToken,
                ],
            ]
        );

        /** @var array<string, mixed> $user */
        $user = $response['user'] ?? [];

        return $user;
    }

    protected function mapIdentity(array $userInfo, array $token): ExternalIdentityDTO
    {
        $email = $userInfo['email'] ?? null;
        $name = trim(($userInfo['first_name'] ?? '') . ' ' . ($userInfo['last_name'] ?? ''));

        return new ExternalIdentityDTO(
            // The identifier is in the profile, and in the token response as a fallback: VK ID
            // sends it in both, and an identity without one cannot be linked to anything.
            providerUserId: (string) ($userInfo['user_id'] ?? $token['user_id'] ?? ''),
            email: $email === null ? null : (string) $email,
            // VK hands out an address only for an account it has confirmed itself, so one that
            // arrives at all is one VK vouches for.
            emailVerified: $email !== null,
            nickname: $name === '' ? null : $name,
            avatarUrl: isset($userInfo['avatar']) ? (string) $userInfo['avatar'] : null,
        );
    }
}
