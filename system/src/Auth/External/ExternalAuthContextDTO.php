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

/**
 * What the core hands a provider about the exchange in progress: where the provider must send
 * the visitor back, and the one-time values guarding the round trip.
 *
 * The provider neither invents nor stores these — the core does, in the PHP session, and checks
 * them when the visitor returns.
 */
final readonly class ExternalAuthContextDTO
{
    public function __construct(
        public string $redirectUri,
        public string $state,
        public string $codeVerifier = '',
        public string $codeChallenge = '',
    ) {
    }
}
