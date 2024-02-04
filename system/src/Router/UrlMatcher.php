<?php

declare(strict_types=1);

namespace Johncms\Router;

use Symfony\Component\Routing\Matcher\RedirectableUrlMatcher;
use Symfony\Component\Routing\Matcher\RedirectableUrlMatcherInterface;

class UrlMatcher extends RedirectableUrlMatcher implements RedirectableUrlMatcherInterface
{
    public function redirect(string $path, string $route, ?string $scheme = null): array
    {
        return [
            '_redirect' => [
                'path'   => $path,
                'route'  => $route,
                'scheme' => $scheme,
            ],
        ];
    }
}
