<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Users;

use Johncms\Users\AuthProviders\AuthProviderInterface;
use Johncms\Users\Exceptions\RuntimeException;

class Authentication
{
    protected array $providers = [];

    public function __construct()
    {
        $providers = di('config')['auth_providers'];
        foreach ($providers as $provider) {
            $this->addProvider($provider);
        }
    }

    public function addProvider(string $provider): void
    {
        if (! is_subclass_of($provider, AuthProviderInterface::class)) {
            throw new RuntimeException(sprintf('The class %s must be implementation of %s', $provider, AuthProviderInterface::class));
        }
        $this->providers[] = $provider;
    }

    public function authenticate(): ?User
    {
        foreach ($this->providers as $provider) {
            $auth = di($provider)->authenticate();
            if ($auth !== null) {
                return $auth;
            }
        }
        return null;
    }
}
