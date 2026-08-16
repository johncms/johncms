<?php

declare(strict_types=1);

namespace Tests\Unit\Auth\External;

use Gettext\Translator;
use Gettext\TranslatorFunctions;
use Johncms\Auth\External\ExternalAuthContextDTO;
use Johncms\Auth\External\ExternalAuthException;
use Johncms\Auth\External\ExternalCallbackDTO;
use Johncms\Auth\External\Providers\VkProvider;
use Johncms\Config\ConfigRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * VK, through VK ID.
 *
 * The classic oauth.vk.com flow is not an option for an application registered today: VK
 * redirects it to VK ID and answers the classic token exchange with a bare "Security Error".
 * What these tests pin is the three things VK ID does differently, all of them mandatory.
 */
final class VkProviderTest extends TestCase
{
    protected function setUp(): void
    {
        TranslatorFunctions::register(new Translator());
        ConfigRepository::init([
            'auth' => [
                'external' => [
                    'providers' => [
                        'vk' => ['client_id' => 'id', 'client_secret' => 'secret', 'enabled' => true],
                    ],
                ],
            ],
        ]);
    }

    public function testTheVisitorIsSentToVkId(): void
    {
        $url = (new VkProvider(new MockHttpClient()))->startUrl($this->context());

        self::assertStringStartsWith('https://id.vk.com/authorize?', $url);
    }

    /**
     * PKCE is required by VK ID rather than optional, and the scopes are separated by spaces —
     * the comma of the classic VK API is one of the ways a flow ends up without the scopes it
     * asked for.
     */
    public function testTheAuthorizeUrlCarriesPkceAndSpaceSeparatedScopes(): void
    {
        $url = (new VkProvider(new MockHttpClient()))->startUrl($this->context());

        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        self::assertSame('challenge', $query['code_challenge']);
        self::assertSame('S256', $query['code_challenge_method']);
        self::assertSame('vkid.personal_info email', $query['scope']);
    }

    public function testTheTokenRequestCarriesTheDeviceIdFromTheCallback(): void
    {
        $sent = [];
        $client = new MockHttpClient(function (string $method, string $url, array $options) use (&$sent): MockResponse {
            $sent[] = ['url' => $url, 'body' => $options['body'] ?? ''];

            return str_contains($url, '/oauth2/auth')
                ? new MockResponse(json_encode(['access_token' => 'token', 'user_id' => 42]))
                : new MockResponse(json_encode(['user' => ['user_id' => 42, 'first_name' => 'Ivan']]));
        });

        (new VkProvider($client))->handleCallback(
            new ExternalCallbackDTO(['code' => 'the-code', 'device_id' => 'the-device']),
            $this->context()
        );

        self::assertStringContainsString('device_id=the-device', $sent[0]['body']);
        self::assertStringContainsString('code_verifier=verifier', $sent[0]['body']);
    }

    /**
     * Without it the token request fails at VK with an error that says nothing, so the flow stops
     * here instead.
     */
    public function testACallbackWithoutADeviceIdIsRefused(): void
    {
        $this->expectException(ExternalAuthException::class);

        (new VkProvider(new MockHttpClient()))
            ->handleCallback(new ExternalCallbackDTO(['code' => 'the-code']), $this->context());
    }

    /**
     * VK ID puts neither the address nor the name in the token or in users.get; they come from
     * /oauth2/user_info, wrapped in a "user" object.
     */
    public function testTheProfileComesFromTheUserInfoEndpoint(): void
    {
        $client = new MockHttpClient([
            new MockResponse(json_encode(['access_token' => 'token', 'user_id' => 42])),
            new MockResponse(json_encode([
                'user' => [
                    'user_id'    => 42,
                    'first_name' => 'Ivan',
                    'last_name'  => 'Petrov',
                    'email'      => 'user@example.com',
                    'avatar'     => 'https://example.com/a.jpg',
                ],
            ])),
        ]);

        $identity = (new VkProvider($client))->handleCallback(
            new ExternalCallbackDTO(['code' => 'the-code', 'device_id' => 'the-device']),
            $this->context()
        );

        self::assertSame('42', $identity->providerUserId);
        self::assertSame('user@example.com', $identity->email);
        self::assertTrue($identity->emailVerified);
        self::assertSame('Ivan Petrov', $identity->nickname);
        self::assertSame('https://example.com/a.jpg', $identity->avatarUrl);
    }

    /**
     * VK hands out the address only when the account granted it, so registering through it cannot
     * count on having one — that is what the "finish signing up" screen is for.
     */
    public function testAnIdentityWithoutAnAddressIsStillUsable(): void
    {
        $client = new MockHttpClient([
            new MockResponse(json_encode(['access_token' => 'token', 'user_id' => 42])),
            new MockResponse(json_encode(['user' => ['user_id' => 42, 'first_name' => 'Ivan']])),
        ]);

        $identity = (new VkProvider($client))->handleCallback(
            new ExternalCallbackDTO(['code' => 'the-code', 'device_id' => 'the-device']),
            $this->context()
        );

        self::assertNull($identity->email);
        self::assertFalse($identity->emailVerified);
    }

    private function context(): ExternalAuthContextDTO
    {
        return new ExternalAuthContextDTO(
            redirectUri: 'https://example.com/auth/vk/callback',
            state: 'the-state',
            codeVerifier: 'verifier',
            codeChallenge: 'challenge',
        );
    }
}
