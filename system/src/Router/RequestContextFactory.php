<?php

declare(strict_types=1);

namespace Johncms\Router;

use Symfony\Component\Routing\RequestContext;

final class RequestContextFactory
{
    public static function createFromGlobals(): RequestContext
    {
        $context = new RequestContext();
        $context->setMethod($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $context->setHost($_SERVER['HTTP_HOST'] ?? 'localhost');
        $context->setScheme(self::resolveScheme());

        $port = (int) ($_SERVER['SERVER_PORT'] ?? 80);
        if ($context->getScheme() === 'https') {
            $context->setHttpsPort($port);
        } else {
            $context->setHttpPort($port);
        }

        return $context;
    }

    private static function resolveScheme(): string
    {
        $https = strtolower((string) ($_SERVER['HTTPS'] ?? ''));
        if ($https === 'on' || $https === '1') {
            return 'https';
        }

        $forwarded = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
        if ($forwarded === 'https') {
            return 'https';
        }

        return 'http';
    }
}
