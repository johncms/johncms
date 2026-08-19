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

/**
 * The captcha providers this installation knows about: the built-in ones plus whatever the
 * installed modules registered.
 *
 * "Known" and "usable" are separate questions, as they are for the sign-in services: a provider
 * whose keys are missing is listed here and in the panel, but the manager will not put it in
 * front of a visitor.
 */
final class CaptchaProviderRegistry
{
    /** @var array<string, CaptchaProviderInterface> */
    private array $providers = [];

    /**
     * @param iterable<CaptchaProviderInterface> $providers
     */
    public function __construct(iterable $providers = [])
    {
        foreach ($providers as $provider) {
            $this->providers[$provider->key()] = $provider;
        }
    }

    public function get(string $key): ?CaptchaProviderInterface
    {
        return $this->providers[$key] ?? null;
    }

    public function has(string $key): bool
    {
        return isset($this->providers[$key]);
    }

    /**
     * Everything registered, configured or not. What the panel lists.
     *
     * @return array<string, CaptchaProviderInterface>
     */
    public function all(): array
    {
        return $this->providers;
    }
}
