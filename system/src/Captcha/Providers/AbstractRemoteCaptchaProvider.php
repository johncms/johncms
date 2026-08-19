<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Captcha\Providers;

use Johncms\Captcha\CaptchaChallenge;
use Johncms\Captcha\CaptchaFailure;
use Johncms\Captcha\CaptchaProviderInterface;
use Johncms\Captcha\CaptchaProviderOptions;
use Johncms\Captcha\CaptchaResult;
use Johncms\Captcha\CaptchaSettingField;
use Johncms\Captcha\CaptchaSettingType;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

/**
 * A captcha somebody else runs, written once.
 *
 * All three shipped services work the same way: a widget of theirs draws itself on the page from
 * a public key, puts a token into a field, and a request to one address says whether that token
 * is good. What differs is the address, the names of the fields and how the answer reads — which
 * is why a provider on top of this is a handful of methods, the way AbstractOAuth2Provider makes
 * a sign-in service one.
 *
 * Nothing is checked locally, so an unreachable service means the site cannot tell visitors from
 * bots at all. That is reported as Unavailable rather than as a wrong answer: it is not the
 * visitor's mistake, and the log has to be able to tell the two apart.
 */
abstract class AbstractRemoteCaptchaProvider implements CaptchaProviderInterface
{
    /**
     * A visitor is waiting in front of a submitted form, so the wait is short: the service is
     * either there or the submission fails on it.
     */
    private const TIMEOUT = 5;

    public function __construct(
        protected readonly HttpClientInterface $httpClient,
        protected readonly LoggerInterface $logger = new NullLogger(),
    ) {
    }

    abstract public function key(): string;

    abstract public function label(): string;

    abstract public function fieldName(): string;

    /** Where the token is checked. */
    abstract protected function verifyUrl(): string;

    /** The template of the widget. */
    abstract protected function template(): string;

    /**
     * The body of the verification request, in the names this service uses for them.
     *
     * @return array<string, string>
     */
    abstract protected function verifyPayload(string $answer, string $secret, ?string $clientIp): array;

    /**
     * What the answer of the service means.
     *
     * @param array<string, mixed> $response
     */
    abstract protected function judge(array $response): CaptchaResult;

    public function isConfigured(): bool
    {
        return $this->siteKey() !== '' && $this->secretKey() !== '';
    }

    public function settingsFields(): array
    {
        return [
            new CaptchaSettingField(
                key: 'site_key',
                type: CaptchaSettingType::Text,
                label: $this->siteKeyLabel(),
                hint: d__('system', 'The key of the widget. It is part of the page and is not a secret'),
            ),
            new CaptchaSettingField(
                key: 'secret_key',
                type: CaptchaSettingType::Password,
                label: $this->secretKeyLabel(),
                hint: d__('system', 'Used to check the answer. Never leaves the server'),
            ),
            ...$this->extraSettingsFields(),
        ];
    }

    public function challenge(string $scope): CaptchaChallenge
    {
        // Nothing is remembered on this side: the service issues the challenge to the browser and
        // is the one asked about it afterwards, so the scope has nothing to key here.
        return new CaptchaChallenge(
            provider: $this->key(),
            template: $this->template(),
            fieldName: $this->fieldName(),
            params: ['site_key' => $this->siteKey()],
        );
    }

    public function verify(string $answer, string $scope, ?string $clientIp = null): CaptchaResult
    {
        $secret = $this->secretKey();

        if ($secret === '') {
            // Reached only when the settings were emptied between the page being drawn and the
            // form being submitted: the manager does not offer an unconfigured provider.
            return CaptchaResult::failed(CaptchaFailure::NotConfigured);
        }

        $answer = trim($answer);

        if ($answer === '') {
            return CaptchaResult::failed(CaptchaFailure::Missing);
        }

        try {
            $response = $this->httpClient->request(
                'POST',
                $this->verifyUrl(),
                ['body' => $this->verifyPayload($answer, $secret, $clientIp), 'timeout' => self::TIMEOUT]
            );

            /** @var array<string, mixed> $data */
            $data = $response->toArray(false);
        } catch (Throwable $throwable) {
            // A timeout, a refused connection, a body that is not JSON: all of them mean the same
            // thing here — nobody can say whether this was a visitor or a bot.
            $this->logger->warning(
                'The captcha service could not be reached',
                ['provider' => $this->key(), 'exception' => $throwable]
            );

            return CaptchaResult::failed(CaptchaFailure::Unavailable);
        }

        return $this->judge($data);
    }

    /**
     * What the service calls the key that goes on the page. Overridden by the providers whose
     * own documentation names it differently, so the field matches what an administrator is
     * copying from.
     */
    protected function siteKeyLabel(): string
    {
        return d__('system', 'Public key');
    }

    protected function secretKeyLabel(): string
    {
        return d__('system', 'Secret key');
    }

    /**
     * The settings this service has beyond the two keys.
     *
     * @return list<CaptchaSettingField>
     */
    protected function extraSettingsFields(): array
    {
        return [];
    }

    protected function options(): CaptchaProviderOptions
    {
        return CaptchaProviderOptions::forProvider($this->key());
    }

    protected function siteKey(): string
    {
        return $this->options()->string('site_key');
    }

    protected function secretKey(): string
    {
        return $this->options()->string('secret_key');
    }

    /**
     * The codes a service returns with a refusal are written for whoever set it up, so they are
     * logged rather than shown; the visitor gets a sentence they can act on.
     *
     * @param array<string, mixed> $response
     */
    protected function logRefusal(array $response): void
    {
        $this->logger->info(
            'The captcha service refused a token',
            ['provider' => $this->key(), 'response' => $response]
        );
    }
}
