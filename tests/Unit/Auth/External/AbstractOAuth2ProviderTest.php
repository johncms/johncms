<?php

declare(strict_types=1);

namespace Tests\Unit\Auth\External;

use Gettext\Translator;
use Gettext\TranslatorFunctions;
use Johncms\Auth\External\ExternalAuthContextDTO;
use Johncms\Auth\External\ExternalAuthException;
use Johncms\Auth\External\ExternalCallbackDTO;
use Johncms\Auth\External\Providers\GithubProvider;
use Johncms\Config\ConfigRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * The authorization code flow shared by every shipped provider, exercised through GitHub.
 *
 * Driven with a mock client rather than against the real service: what is being checked is the
 * flow — what we send, and what we make of the answers — not GitHub's availability.
 */
final class AbstractOAuth2ProviderTest extends TestCase
{
    protected function setUp(): void
    {
        TranslatorFunctions::register(new Translator());
        ConfigRepository::init([
            'auth' => [
                'external' => [
                    'providers' => [
                        'github' => ['client_id' => 'id', 'client_secret' => 'secret', 'enabled' => true],
                    ],
                ],
            ],
        ]);
    }

    public function testTheAuthorizeUrlCarriesTheStateAndThePkceChallenge(): void
    {
        $provider = new GithubProvider(new MockHttpClient());

        $url = $provider->startUrl($this->context());

        self::assertStringStartsWith('https://github.com/login/oauth/authorize?', $url);
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
        self::assertSame('the-state', $query['state']);
        self::assertSame('challenge', $query['code_challenge']);
        self::assertSame('S256', $query['code_challenge_method']);
    }

    public function testTheCodeIsExchangedAndTheProfileMapped(): void
    {
        $client = new MockHttpClient([
            new MockResponse(json_encode(['access_token' => 'token'])),
            new MockResponse(json_encode([
                'id'         => 42,
                'login'      => 'octocat',
                'email'      => 'octocat@example.com',
                'avatar_url' => 'https://example.com/a.png',
            ])),
        ]);

        $identity = (new GithubProvider($client))
            ->handleCallback(new ExternalCallbackDTO(['code' => 'the-code']), $this->context());

        self::assertSame('42', $identity->providerUserId);
        self::assertSame('octocat', $identity->nickname);
        self::assertSame('octocat@example.com', $identity->email);
        self::assertTrue($identity->emailVerified);
    }

    /**
     * The visitor pressed "cancel" at the provider. Its own wording is not shown: it is written
     * for developers and often names the client id.
     */
    public function testARefusalAtTheProviderEndsTheFlow(): void
    {
        $this->expectException(ExternalAuthException::class);

        (new GithubProvider(new MockHttpClient()))
            ->handleCallback(new ExternalCallbackDTO(['error' => 'access_denied']), $this->context());
    }

    public function testACallbackWithoutACodeIsRefused(): void
    {
        $this->expectException(ExternalAuthException::class);

        (new GithubProvider(new MockHttpClient()))
            ->handleCallback(new ExternalCallbackDTO([]), $this->context());
    }

    /**
     * Some services answer 200 with an error object in the body, so the status alone does not say
     * whether the exchange worked.
     */
    public function testAnErrorObjectInTheBodyIsAFailureDespiteTheStatus(): void
    {
        $client = new MockHttpClient([new MockResponse(json_encode(['error' => 'bad_verification_code']))]);

        $this->expectException(ExternalAuthException::class);

        (new GithubProvider($client))
            ->handleCallback(new ExternalCallbackDTO(['code' => 'the-code']), $this->context());
    }

    public function testAServiceThatCannotBeReachedIsAFailureRatherThanAFatalError(): void
    {
        $client = new MockHttpClient(static function (): MockResponse {
            return new MockResponse('', ['error' => 'connection refused']);
        });

        $this->expectException(ExternalAuthException::class);

        (new GithubProvider($client))
            ->handleCallback(new ExternalCallbackDTO(['code' => 'the-code']), $this->context());
    }

    /**
     * A provider whose keys the site never filled in is never offered: a button leading to
     * somebody else's error page helps nobody.
     */
    public function testAProviderWithoutKeysIsNotConfigured(): void
    {
        ConfigRepository::init([
            'auth' => ['external' => ['providers' => ['github' => ['enabled' => true]]]],
        ]);

        self::assertFalse((new GithubProvider(new MockHttpClient()))->isConfigured());
    }

    private function context(): ExternalAuthContextDTO
    {
        return new ExternalAuthContextDTO(
            redirectUri: 'https://example.com/auth/github/callback',
            state: 'the-state',
            codeVerifier: 'verifier',
            codeChallenge: 'challenge',
        );
    }
}
