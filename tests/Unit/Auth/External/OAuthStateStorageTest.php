<?php

declare(strict_types=1);

namespace Tests\Unit\Auth\External;

use Gettext\Translator;
use Gettext\TranslatorFunctions;
use Johncms\Auth\External\ExternalAuthException;
use Johncms\Auth\External\OAuthStateStorage;
use Johncms\Http\Session;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * The values that make the round trip to a provider safe.
 *
 * Without them the callback can be replayed: an attacker starts a flow with their own account at
 * the provider and hands the resulting URL to a signed-in victim, whose account then gets linked
 * to theirs.
 */
final class OAuthStateStorageTest extends TestCase
{
    private Session $session;

    private OAuthStateStorage $storage;

    protected function setUp(): void
    {
        TranslatorFunctions::register(new Translator());
        $this->session = new Session(new MockArraySessionStorage());
        $this->storage = new OAuthStateStorage($this->session);
    }

    public function testAStartedFlowIsConsumedByItsOwnState(): void
    {
        $context = $this->storage->start('github', 'https://example.com/auth/github/callback', 'sign_in', null);

        $flow = $this->storage->consume('github', $context->state);

        self::assertSame('sign_in', $flow['intent']);
        self::assertSame($context->codeVerifier, $flow['context']->codeVerifier);
    }

    public function testAStateIsGoodForOneAttempt(): void
    {
        $context = $this->storage->start('github', '/callback', 'sign_in');
        $this->storage->consume('github', $context->state);

        $this->expectException(ExternalAuthException::class);

        $this->storage->consume('github', $context->state);
    }

    public function testAForeignStateIsRefused(): void
    {
        $this->storage->start('github', '/callback', 'sign_in');

        $this->expectException(ExternalAuthException::class);

        $this->storage->consume('github', 'somebody-elses-state');
    }

    /**
     * A callback of one provider must not close the flow started with another: the code it
     * carries was issued by somebody else.
     */
    public function testACallbackOfAnotherProviderIsRefused(): void
    {
        $context = $this->storage->start('github', '/callback', 'sign_in');

        $this->expectException(ExternalAuthException::class);

        $this->storage->consume('google', $context->state);
    }

    public function testACallbackWithoutAFlowIsRefused(): void
    {
        $this->expectException(ExternalAuthException::class);

        $this->storage->consume('github', 'anything');
    }

    /**
     * The challenge is what the provider was given; sending it back the verifier is what proves
     * the flow is the same one.
     */
    public function testTheChallengeIsTheS256DigestOfTheVerifier(): void
    {
        $context = $this->storage->start('github', '/callback', 'sign_in');

        $expected = rtrim(strtr(base64_encode(hash('sha256', $context->codeVerifier, true)), '+/', '-_'), '=');

        self::assertSame($expected, $context->codeChallenge);
    }

    /**
     * The address the provider was given, not one rebuilt from the callback request. Providers
     * compare the two byte for byte, and a scheme decided by a proxy header is enough to make
     * them differ — which VK reports as "Security Error".
     */
    public function testTheRedirectUriComesBackAsItWasSent(): void
    {
        $context = $this->storage->start('vk', 'https://example.com/auth/vk/callback', 'sign_in');

        $flow = $this->storage->consume('vk', $context->state);

        self::assertSame('https://example.com/auth/vk/callback', $flow['context']->redirectUri);
    }

    public function testTheIntentAndTheAccountAreDecidedWhenTheFlowStarts(): void
    {
        $context = $this->storage->start('github', '/callback', 'link', 7);

        $flow = $this->storage->consume('github', $context->state);

        self::assertSame('link', $flow['intent']);
        self::assertSame(7, $flow['user_id']);
    }
}
