<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Http;

/**
 * The address the site is served on, for the absolute URLs that leave it.
 *
 * The site setting comes first and the request only fills in for it. Behind a proxy that
 * terminates TLS the request looks like plain HTTP unless `http.trusted_proxies` names that
 * proxy, so an address built from the request alone would say `http://` on an HTTPS site — and an
 * external sign-in service compares the address we register against the one we send it, refusing
 * the exchange when the two differ.
 *
 * One place for it because two screens must agree: the panel shows the callback address to paste
 * into the application at the provider, and the flow sends it. A difference between them is the
 * error nobody can diagnose from the outside.
 */
final readonly class SiteBaseUrl
{
    /**
     * @param Request|null $request The request being served, when there is one. Console runs have
     *                              nothing but the setting.
     */
    public function resolve(?Request $request = null): string
    {
        $base = rtrim((string) config('johncms.homeurl', ''), '/');

        if ($base === '' && $request !== null) {
            $base = $request->getSchemeAndHttpHost();
        }

        // A site reachable over HTTPS whose setting still says http:// would hand out addresses
        // that providers refuse, so a request that arrived over TLS overrides the scheme.
        //
        // Behind a TLS-terminating proxy this depends on `http.trusted_proxies` naming that
        // proxy: without it HttpFoundation ignores X-Forwarded-Proto, and rightly so — an
        // untrusted header cannot be allowed to decide anything. The answer is to configure the
        // proxy, not to read the header behind the framework's back.
        if ($request?->isSecure() === true && str_starts_with($base, 'http://')) {
            $base = 'https://' . substr($base, 7);
        }

        return $base;
    }
}
