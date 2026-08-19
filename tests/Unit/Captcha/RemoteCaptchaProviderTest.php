<?php

declare(strict_types=1);

namespace Tests\Unit\Captcha;

use Gettext\Translator;
use Gettext\TranslatorFunctions;
use Johncms\Captcha\CaptchaFailure;
use Johncms\Captcha\Providers\HCaptchaProvider;
use Johncms\Captcha\Providers\RecaptchaV3Provider;
use Johncms\Captcha\Providers\SmartCaptchaProvider;
use Johncms\Config\ConfigRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * What the shipped services are asked and what is made of their answers.
 *
 * Driven with a mock client: what is being checked is the exchange, not whether Google and
 * Yandex are up.
 */
final class RemoteCaptchaProviderTest extends TestCase
{
    protected function setUp(): void
    {
        TranslatorFunctions::register(new Translator());
        $this->configureKeys();
    }

    public function testAServiceThatSaysOkLetsTheSubmissionThrough(): void
    {
        $provider = new SmartCaptchaProvider($this->client(['status' => 'ok']));

        self::assertTrue($provider->verify('token', 'guestbook')->passed);
    }

    public function testARefusedTokenIsAMismatch(): void
    {
        $provider = new SmartCaptchaProvider($this->client(['status' => 'failed']));

        self::assertSame(CaptchaFailure::Mismatch, $provider->verify('token', 'guestbook')->failure);
    }

    /**
     * The secret and the address of the visitor reach the service, and the secret is sent in the
     * body rather than in the query: a URL is what ends up in access logs and in proxy caches.
     */
    public function testTheRequestCarriesTheSecretAndTheVisitorAddress(): void
    {
        $seen = null;
        $client = new MockHttpClient(
            function (string $method, string $url, array $options) use (&$seen): MockResponse {
                $seen = ['method' => $method, 'url' => $url, 'body' => $options['body'] ?? ''];

                return new MockResponse((string) json_encode(['status' => 'ok']));
            }
        );

        (new SmartCaptchaProvider($client))->verify('token', 'guestbook', '203.0.113.7');

        self::assertSame('POST', $seen['method']);
        self::assertSame('https://smartcaptcha.yandexcloud.net/validate', $seen['url']);
        parse_str((string) $seen['body'], $body);
        self::assertSame('server-key', $body['secret']);
        self::assertSame('token', $body['token']);
        self::assertSame('203.0.113.7', $body['ip']);
    }

    /**
     * Nothing is checked on this side, so a service that cannot be reached leaves the question
     * unanswered. Told apart from a wrong answer: it is not the visitor's mistake.
     */
    public function testAnUnreachableServiceIsReportedAsUnavailable(): void
    {
        $client = new MockHttpClient(static function (): MockResponse {
            throw new class ('The service is down') extends \RuntimeException implements TransportExceptionInterface {
            };
        });

        $result = (new HCaptchaProvider($client))->verify('token', 'contacts');

        self::assertFalse($result->passed);
        self::assertSame(CaptchaFailure::Unavailable, $result->failure);
    }

    public function testAnEmptyAnswerIsNotSentAnywhere(): void
    {
        // A client with no responses raises as soon as a request is made, so a request made here
        // fails the test rather than passing unnoticed.
        $result = (new HCaptchaProvider(new MockHttpClient([])))->verify('', 'contacts');

        self::assertSame(CaptchaFailure::Missing, $result->failure);
    }

    public function testAScoreAboveTheThresholdPasses(): void
    {
        $provider = new RecaptchaV3Provider($this->client(['success' => true, 'score' => 0.9]));

        self::assertTrue($provider->verify('token', 'registration')->passed);
    }

    /**
     * v3 offers a visitor it doubts nothing to solve, so a low score is its own kind of failure —
     * the message says the verification did not pass, not that a code was mistyped.
     */
    public function testAScoreBelowTheThresholdIsRefused(): void
    {
        $provider = new RecaptchaV3Provider($this->client(['success' => true, 'score' => 0.1]));

        self::assertSame(CaptchaFailure::LowScore, $provider->verify('token', 'registration')->failure);
    }

    public function testTheThresholdComesFromTheSettings(): void
    {
        $this->configureKeys(['score_threshold' => 0.2]);
        $provider = new RecaptchaV3Provider($this->client(['success' => true, 'score' => 0.3]));

        self::assertTrue($provider->verify('token', 'registration')->passed);
    }

    /**
     * A token is good for two minutes and for one check; a form left open long enough is the
     * ordinary case, and worth telling apart from a refusal.
     */
    public function testASpentTokenIsReportedAsExpired(): void
    {
        $provider = new RecaptchaV3Provider(
            $this->client(['success' => false, 'error-codes' => ['timeout-or-duplicate']])
        );

        self::assertSame(CaptchaFailure::Expired, $provider->verify('token', 'registration')->failure);
    }

    public function testAProviderWithoutItsKeysIsNotOffered(): void
    {
        ConfigRepository::init([]);

        self::assertFalse((new SmartCaptchaProvider(new MockHttpClient([])))->isConfigured());
        self::assertSame(
            CaptchaFailure::NotConfigured,
            (new SmartCaptchaProvider(new MockHttpClient([])))->verify('token', 'guestbook')->failure
        );
    }

    /**
     * @param array<string, mixed> $answer
     */
    private function client(array $answer): HttpClientInterface
    {
        return new MockHttpClient(new MockResponse((string) json_encode($answer)));
    }

    /**
     * @param array<string, mixed> $extra Settings of the reCAPTCHA provider beyond its keys.
     */
    private function configureKeys(array $extra = []): void
    {
        ConfigRepository::init([
            'captcha' => [
                'providers' => [
                    'smartcaptcha' => ['options' => ['site_key' => 'client-key', 'secret_key' => 'server-key']],
                    'hcaptcha'     => ['options' => ['site_key' => 'site', 'secret_key' => 'secret']],
                    'recaptcha_v3' => ['options' => ['site_key' => 'site', 'secret_key' => 'secret'] + $extra],
                ],
            ],
        ]);
    }
}
