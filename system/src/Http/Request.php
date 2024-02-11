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

use GuzzleHttp\Psr7\CachingStream;
use GuzzleHttp\Psr7\LazyOpenStream;
use GuzzleHttp\Psr7\ServerRequest;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

class Request extends ServerRequest
{
    public const POST_SESSION_KEY = '_POST';

    protected array | null $json = null;

    /**
     * Return a ServerRequest populated with superglobals:
     * $_GET
     * $_POST
     * $_COOKIE
     * $_FILES
     * $_SERVER
     */
    public static function fromGlobals(): ServerRequestInterface
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $headers = getallheaders();
        $uri = self::getUriFromGlobals();
        $body = new CachingStream(new LazyOpenStream('php://input', 'r+'));
        $protocol = isset($_SERVER['SERVER_PROTOCOL']) ? str_replace('HTTP/', '', $_SERVER['SERVER_PROTOCOL']) : '1.1';
        $serverRequest = new self($method, $uri, $headers, $body, $protocol, $_SERVER);

        $post = $_POST;
        $sessionPost = di(Session::class)->getFlash(Request::POST_SESSION_KEY);
        if ($sessionPost !== null) {
            $post = array_merge($sessionPost, $post);
        }

        return $serverRequest
            ->withCookieParams($_COOKIE)
            ->withQueryParams($_GET)
            ->withParsedBody($post)
            ->withUploadedFiles(self::normalizeFiles($_FILES))
            ->withJson();
    }

    protected function withJson(): static
    {
        $body = $this->getBody();
        if (! empty($body->getContents())) {
            try {
                $this->json = json_decode($body->getContents(), true, 512, JSON_THROW_ON_ERROR);
            } catch (Throwable) {
            }
        }
        return $this;
    }

    public function getQuery(string $name, mixed $default = null, int $filter = FILTER_DEFAULT, mixed $options = 0): mixed
    {
        return $this->filterVar($name, $this->getQueryParams(), $filter, $options) ?? $default;
    }

    public function getPost(string $name, mixed $default = null, int $filter = FILTER_DEFAULT, mixed $options = 0): mixed
    {
        return $this->filterVar($name, $this->getParsedBody(), $filter, $options) ?? $default;
    }

    public function getJson(string $name = '', mixed $default = null, int $filter = FILTER_DEFAULT, mixed $options = 0): mixed
    {
        if (empty($name)) {
            return $this->json ?? [];
        }
        return $this->filterVar($name, $this->json ?? [], $filter, $options) ?? $default;
    }

    public function getCookie(string $name, mixed $default = null, int $filter = FILTER_DEFAULT, mixed $options = 0): mixed
    {
        return $this->filterVar($name, $this->getCookieParams(), $filter, $options) ?? $default;
    }

    public function getServer(string $name, mixed $default = null, int $filter = FILTER_DEFAULT, mixed $options = 0): mixed
    {
        return $this->filterVar($name, $this->getServerParams(), $filter, $options) ?? $default;
    }

    private function filterVar(int | string $key, mixed $var, int $filter, mixed $options): mixed
    {
        if (is_array($var) && isset($var[$key])) {
            if (is_array($var[$key])) {
                $result = [];
                foreach ($var[$key] as $k => $v) {
                    $result[$k] = $this->filterVar($k, $var[$key], $filter, $options);
                }
            } else {
                $value = is_string($var[$key]) ? trim($var[$key]) : $var[$key];
                $result = filter_var($value, $filter, $options);
            }

            if (false !== $result) {
                return $result;
            }
        }

        return null;
    }

    /**
     * Getting a query string without the specified parameters.
     */
    public function getQueryString(array $removeParams = [], array $additionalParams = []): string
    {
        $queryParams = $this->getQueryParams();
        if (! empty($removeParams)) {
            $queryParams = array_diff_key($queryParams, array_flip($removeParams));
        }
        $queryParams = array_merge($queryParams, $additionalParams);
        $str = http_build_query($queryParams);

        return $this->getUri()->getPath() . (! empty($str) ? '?' . $str : '');
    }

    /**
     * Checking that the site is open over https.
     *
     * @psalm-suppress PossiblyNullArgument
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

    public function getUserAgent(): ?string
    {
        return htmlspecialchars($this->getServer('HTTP_USER_AGENT', ''));
    }

    public function getIp(): ?string
    {
        return $this->getServer('REMOTE_ADDR', '127.0.0.1', FILTER_VALIDATE_IP);
    }

    public function getIpViaProxy(): ?string
    {
        $ip = $this->getServer('HTTP_X_FORWARDED_FOR', '127.0.0.1', FILTER_VALIDATE_IP);
        return $ip !== '127.0.0.1' ? $ip : null;
    }

    public function isPost(): bool
    {
        return $this->getMethod() === 'POST';
    }

    public function isXmlHttpRequest(): bool
    {
        $header = $this->getHeader('X-Requested-With')[0] ?? '';
        return $header == 'XMLHttpRequest';
    }
}
