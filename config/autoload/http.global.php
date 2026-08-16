<?php

declare(strict_types=1);

// HTTP layer configuration.
return [
    'http' => [
        // Trusted reverse-proxy IP addresses / CIDR ranges. When the application runs behind
        // a proxy (Traefik, a CDN, a load balancer), list its addresses here so the framework
        // reads the real visitor address and scheme from the X-Forwarded-* headers. Until then
        // those headers are ignored entirely and the peer address is used as-is — the only
        // value that cannot be forged.
        //
        // Fill this in when, and only when, something in front of the application overwrites
        // X-Forwarded-For. Examples:
        //
        //     'trusted_proxies' => ['10.0.1.5'],            // one known reverse proxy
        //     'trusted_proxies' => ['173.245.48.0/20'],     // a CDN edge range
        //
        // Without this, X-Forwarded-Proto is ignored and an HTTPS site behind a TLS-terminating
        // proxy looks like plain HTTP to the application: cookies lose the Secure flag and
        // generated absolute URLs (the OAuth callback, password-reset links) come out as http://.
        //
        // Do NOT put 'private_ranges' (or a broad private CIDR) here without checking how the
        // application is exposed. The bundled nginx talks to PHP-FPM over FastCGI and passes
        // the visitor's own X-Forwarded-For through untouched, while REMOTE_ADDR is whatever
        // connected to nginx. With Docker port publishing that peer is the bridge gateway
        // (172.x.0.1) for every visitor, so trusting private ranges would let any client on
        // the internet pick their own address — defeating IP bans and per-IP flood limits.
        //
        // Note that config files are merged with array_replace_recursive(), so a non-empty
        // default here could not be switched back off from a *.local.php override.
        'trusted_proxies' => [],

        // Bitmask of forwarded headers to trust, using Symfony Request::HEADER_* constants.
        // Null keeps the standard X-Forwarded-For/Host/Proto/Port set. Only relevant when
        // 'trusted_proxies' is non-empty.
        //
        // Security note: the default set trusts X-Forwarded-Host, which controls the host used
        // in generated absolute URLs (password-reset links, etc.). Make sure your proxy
        // overwrites (does not pass through) a client-supplied X-Forwarded-Host, otherwise a
        // client can inject an arbitrary host. If the proxy does not manage that header, narrow
        // this set to Request::HEADER_X_FORWARDED_FOR | _PROTO | _PORT.
        'trusted_headers' => null,

        // Host names the application is willing to serve, as regular expressions WITHOUT
        // delimiters (they are wrapped in {...}i internally). This is the countermeasure to
        // host-header injection: without it the Host header — or X-Forwarded-Host, when a proxy
        // is trusted — is taken at face value and ends up in generated absolute URLs such as
        // password-reset links.
        //
        // Anchor every pattern with ^ and $: matching is not anchored, so 'example\.com' would
        // also accept the host 'example.com.attacker.net'.
        //
        // An empty list keeps the permissive default, which is fine when the web server itself
        // only answers for known host names. Fill it in when the application is reachable
        // through a catch-all virtual host. Example:
        //
        //     'trusted_hosts' => ['^example\.com$', '^.+\.example\.com$'],
        'trusted_hosts' => [],
    ],
];
