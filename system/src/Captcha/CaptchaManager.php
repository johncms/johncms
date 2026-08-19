<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Captcha;

use Psr\Log\LoggerInterface;

/**
 * The captcha the site uses, as everything else sees it.
 *
 * Forms and the login flow ask this and never a provider directly: which provider is in use is a
 * setting, and it may be one a module brought along.
 */
final class CaptchaManager
{
    /**
     * Used when the configured provider is unknown or unusable. Never a way through: a site whose
     * remote service lost its keys falls back to the picture instead of letting everything past,
     * which is what switching the captcha off silently would amount to.
     */
    private const FALLBACK_PROVIDER = 'image';

    public function __construct(
        private readonly CaptchaProviderRegistry $registry,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function provider(): CaptchaProviderInterface
    {
        $configured = (string) config('captcha.default', self::FALLBACK_PROVIDER);
        $provider = $this->registry->get($configured);

        if ($provider === null) {
            $this->logger->warning(
                'Unknown captcha provider is configured, falling back to the built-in one.',
                ['configured' => $configured]
            );
        } elseif (! $provider->isConfigured()) {
            $this->logger->warning(
                'The configured captcha provider is missing its settings, falling back to the built-in one.',
                ['configured' => $configured]
            );
            $provider = null;
        }

        return $provider
            ?? $this->registry->get(self::FALLBACK_PROVIDER)
            ?? throw new CaptchaException(
                sprintf('Neither the "%s" captcha provider nor the built-in one is registered.', $configured)
            );
    }

    /**
     * The name of the request field the answer arrives in. A form reads its value by this name.
     */
    public function fieldName(): string
    {
        return $this->provider()->fieldName();
    }

    public function challenge(string $scope): CaptchaChallenge
    {
        return $this->provider()->challenge($scope);
    }

    public function verify(string $answer, string $scope, ?string $clientIp = null): CaptchaResult
    {
        return $this->provider()->verify($answer, $scope, $clientIp);
    }
}
