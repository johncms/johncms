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

use GuzzleHttp\Psr7\{CachingStream, LazyOpenStream, ServerRequest};
use Psr\Http\Message\ServerRequestInterface;

class Request extends ServerRequest
{
    public const POST_SESSION_KEY = '_POST';

    /**
     * Return a ServerRequest populated with superglobals:
     * $_GET
     * $_POST
     * $_COOKIE
     * $_FILES
     * $_SERVER
     *
     * @return ServerRequestInterface
     */
    public static function fromGlobals(): ServerRequestInterface
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $headers = getallheaders();
        $uri = self::getUriFromGlobals();
        $body = new CachingStream(new LazyOpenStream('php://input', 'r+'));
        $protocol = isset($_SERVER['SERVER_PROTOCOL']) ? str_replace('HTTP/', '', $_SERVER['SERVER_PROTOCOL']) : '1.1';
        $serverRequest = new self($method, $uri, /** @scrutinizer ignore-type */ $headers, $body, $protocol, $_SERVER);

        $post = $_POST;
        $sessionPost = di(Session::class)->getFlash(Request::POST_SESSION_KEY);
        if ($sessionPost !== null) {
            $post = array_merge($sessionPost, $post);
        }

        return $serverRequest
            ->withCookieParams($_COOKIE)
            ->withQueryParams($_GET)
            ->withParsedBody($post)
            ->withUploadedFiles(self::normalizeFiles($_FILES));
    }

    public function getQuery(string $name, mixed $default = null, int $filter = FILTER_DEFAULT, mixed $options = 0): mixed
    {
        return $this->filterVar($name, $this->getQueryParams(), $filter, $options) ?? $default;
    }

    public function getPost(string $name, mixed $default = null, int $filter = FILTER_DEFAULT, mixed $options = 0): mixed
    {
        return $this->filterVar($name, $this->getParsedBody(), $filter, $options) ?? $default;
    }

    public function getCookie(string $name, mixed $default = null, int $filter = FILTER_DEFAULT, mixed $options = 0): mixed
    {
        return $this->filterVar($name, $this->getCookieParams(), $filter, $options) ?? $default;
    }

    public function getServer(string $name, mixed $default = null, int $filter = FILTER_DEFAULT, mixed $options = 0): mixed
    {
        return $this->filterVar($name, $this->getServerParams(), $filter, $options) ?? $default;
    }

    private function filterVar(int|string $key, mixed $var, int $filter, mixed $options): mixed
    {
        if (is_array($var) && isset($var[$key])) {
            if (is_array($var[$key])) {
                $result = [];
                foreach ($var[$key] as $k => $v) {
                    $result[$k] = $this->filterVar($k, $var[$key], $filter, $options);
                }
            } else {
                $result = filter_var(trim($var[$key]), $filter, $options);
            }

            if (false !== $result) {
                return $result;
            }
        }

        return null;
    }

    /**
     * Getting a query string without the specified parameters.
     *
     * @param array $remove_params
     * @return string
     */
    public function getQueryString(array $remove_params = []): string
    {
        $query_params = $this->getQueryParams();
        if (! empty($remove_params)) {
            $query_params = array_diff_key($query_params, array_flip($remove_params));
        }
        $str = http_build_query($query_params);

        return $this->getUri()->getPath() . (! empty($str) ? '?' . $str : '');
    }

    /**
     * Checking that the site is open over https.
     *
     * @psalm-suppress PossiblyNullArgument
     * @return bool
     */
    public function isHttps(): bool
    {
        if ($this->getServer('SERVER_PORT', FILTER_VALIDATE_INT) === 443) {
            return true;
        }

        $https = strtolower($this->getServer('HTTPS', ''));
        if ($https === 'on' || $https === '1') {
            return true;
        }

        if (
            strtolower($this->getServer('HTTP_X_FORWARDED_PROTO', '')) === 'https' ||
            strtolower($this->getServer('HTTP_X_FORWARDED_SSL', '')) === 'on'
        ) {
            return true;
        }

        return false;
    }

    public function userAgent(): ?string
    {
        return $this->getServer('HTTP_USER_AGENT', '', FILTER_SANITIZE_STRING);
    }

    public function ip(): ?string
    {
        return $this->getServer('REMOTE_ADDR', '127.0.0.1', FILTER_VALIDATE_IP);
    }

    public function ipViaProxy(): ?string
    {
        return $this->getServer('HTTP_X_FORWARDED_FOR', '127.0.0.1', FILTER_VALIDATE_IP);
    }
}
