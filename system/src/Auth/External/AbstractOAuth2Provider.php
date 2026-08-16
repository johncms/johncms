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

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

/**
 * The authorization code flow, written once.
 *
 * All four shipped providers are the same exchange with different URLs and different field names
 * in the answer, so a provider on top of this is three addresses and a mapping — which is the
 * point: a module adding Discord or Mail.ru should not have to think about PKCE or about what to
 * do when the token endpoint answers with an error object and a 200.
 *
 * Written against symfony/http-client rather than league/oauth2-client: the league package would
 * impose its own interface on the authors of modules and drags in a provider package per service,
 * maintained by whoever happens to maintain it.
 */
abstract class AbstractOAuth2Provider implements ExternalIdentityProviderInterface
{
    public function __construct(
        protected readonly HttpClientInterface $httpClient,
        protected readonly LoggerInterface $logger = new NullLogger(),
    ) {
    }

    abstract public function key(): string;

    abstract public function label(): string;

    /** Where the visitor authorizes us. */
    abstract protected function authorizeUrl(): string;

    /** Where the code is exchanged for a token. */
    abstract protected function tokenUrl(): string;

    /** Where the token buys the profile. */
    abstract protected function userInfoUrl(): string;

    /** What we ask the provider for. */
    abstract protected function scope(): string;

    /**
     * The provider's answer, as this core understands identities.
     *
     * @param array<string, mixed> $userInfo
     * @param array<string, mixed> $token    The token response, for the providers that put half
     *                                       the profile in it (VK sends the email there).
     */
    abstract protected function mapIdentity(array $userInfo, array $token): ExternalIdentityDTO;

    public function icon(): string
    {
        return $this->key();
    }

    public function isConfigured(): bool
    {
        $settings = $this->settings();

        return $settings->enabled && $settings->areComplete();
    }

    public function startUrl(ExternalAuthContextDTO $context): string
    {
        return $this->authorizeUrl() . '?' . http_build_query($this->authorizeParams($context));
    }

    public function handleCallback(ExternalCallbackDTO $callback, ExternalAuthContextDTO $context): ExternalIdentityDTO
    {
        // The visitor pressed "cancel", or the provider refused us. Its own wording is not shown:
        // it is written for developers and often names the client id.
        if ($callback->get('error') !== '') {
            throw new ExternalAuthException(__('The sign-in was cancelled'));
        }

        $code = $callback->get('code');

        if ($code === '') {
            throw new ExternalAuthException(__('The service did not return a sign-in code'));
        }

        $token = $this->exchangeCode($code, $context, $callback);
        $accessToken = (string) ($token['access_token'] ?? '');

        if ($accessToken === '') {
            throw new ExternalAuthException(__('The service did not return an access token'));
        }

        return $this->mapIdentity($this->fetchUserInfo($accessToken, $token), $token);
    }

    /**
     * @return array<string, string>
     */
    protected function authorizeParams(ExternalAuthContextDTO $context): array
    {
        return [
            'client_id'             => $this->settings()->clientId,
            'redirect_uri'          => $context->redirectUri,
            'response_type'         => 'code',
            'scope'                 => $this->scope(),
            'state'                 => $context->state,
            'code_challenge'        => $context->codeChallenge,
            'code_challenge_method' => 'S256',
        ];
    }

    /**
     * Buys a token with the code.
     *
     * The callback is passed on because some services put more than the code into it — VK ID
     * sends a `device_id` that the token request will not work without.
     *
     * @return array<string, mixed>
     */
    protected function exchangeCode(
        string $code,
        ExternalAuthContextDTO $context,
        ExternalCallbackDTO $callback,
    ): array {
        $settings = $this->settings();

        return $this->request(
            'POST',
            $this->tokenUrl(),
            [
                'headers' => ['Accept' => 'application/json'],
                'body'    => [
                    'grant_type'    => 'authorization_code',
                    'code'          => $code,
                    'client_id'     => $settings->clientId,
                    'client_secret' => $settings->clientSecret,
                    'redirect_uri'  => $context->redirectUri,
                    'code_verifier' => $context->codeVerifier,
                ],
            ]
        );
    }

    /**
     * @param array<string, mixed> $token
     *
     * @return array<string, mixed>
     */
    protected function fetchUserInfo(string $accessToken, array $token): array
    {
        return $this->request(
            'GET',
            $this->userInfoUrl(),
            [
                'headers' => [
                    'Accept'        => 'application/json',
                    'Authorization' => 'Bearer ' . $accessToken,
                ],
            ]
        );
    }

    protected function settings(): ProviderSettings
    {
        return ProviderSettings::forProvider($this->key());
    }

    /**
     * One request to the service, with everything that can go wrong turned into one exception.
     *
     * Timeouts are short on purpose: a visitor is waiting in front of a blank page, and a service
     * that has not answered in ten seconds is not going to.
     *
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    protected function request(string $method, string $url, array $options): array
    {
        try {
            $response = $this->httpClient->request($method, $url, $options + ['timeout' => 10]);
            /** @var array<string, mixed> $data */
            $data = $response->toArray(false);
        } catch (Throwable $throwable) {
            // Everything the client can raise — a timeout, a refused connection, a body that is
            // not JSON — means the same thing to the visitor waiting in front of a blank page.
            $this->logger->warning(
                'The sign-in service could not be reached',
                ['provider' => $this->key(), 'url' => $url, 'exception' => $throwable]
            );

            throw new ExternalAuthException(__('The service is not responding, please try again later'));
        }

        // Some services answer 200 with an error object in the body, so the status alone is not
        // the answer to "did this work".
        if (isset($data['error'])) {
            // Logged rather than shown: the wording of these ("Security Error", "invalid_grant")
            // is written for whoever registered the application, and it is the only thing that
            // says what to fix. The visitor gets a sentence they can act on instead.
            $this->logger->warning(
                'The sign-in service refused the request',
                [
                    'provider'    => $this->key(),
                    'url'         => $url,
                    'error'       => (string) $data['error'],
                    'description' => (string) ($data['error_description'] ?? ''),
                ]
            );

            throw new ExternalAuthException(__('The service refused the sign-in'));
        }

        return $data;
    }
}
