<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\System\Http;

use GuzzleHttp\Psr7\{
    CachingStream,
    LazyOpenStream,
    ServerRequest
};
use Psr\Http\Message\ServerRequestInterface;

class Request extends ServerRequest
{
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
    public static function fromGlobals()
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $headers = getallheaders();
        $uri = self::getUriFromGlobals();
        $body = new CachingStream(new LazyOpenStream('php://input', 'r+'));
        $protocol = isset($_SERVER['SERVER_PROTOCOL']) ? str_replace('HTTP/', '', $_SERVER['SERVER_PROTOCOL']) : '1.1';
        $serverRequest = new self($method, $uri, /** @scrutinizer ignore-type */ $headers, $body, $protocol, $_SERVER);

        return $serverRequest
            ->withCookieParams($_COOKIE)
            ->withQueryParams($_GET)
            ->withParsedBody($_POST)
            ->withUploadedFiles(self::normalizeFiles($_FILES));
    }

    /**
     * @param string $name
     * @param null|mixed $default
     * @param int $filter
     * @param mixed $options
     * @return mixed|null
     */
    public function getQuery(string $name, $default = null, int $filter = FILTER_DEFAULT, $options = null)
    {
        return $this->filterVar($name, $this->getQueryParams(), $filter, $options)
            ?? $default;
    }

    /**
     * @param string $name
     * @param null|mixed $default
     * @param int $filter
     * @param mixed $options
     * @return mixed|null
     */
    public function getPost(string $name, $default = null, int $filter = FILTER_DEFAULT, $options = null)
    {
        return $this->filterVar($name, $this->getParsedBody(), $filter, $options)
            ?? $default;
    }

    /**
     * @param string $name
     * @param null|mixed $default
     * @param int $filter
     * @param mixed $options
     * @return mixed|null
     */
    public function getCookie(string $name, $default = null, int $filter = FILTER_DEFAULT, $options = null)
    {
        return $this->filterVar($name, $this->getCookieParams(), $filter, $options)
            ?? $default;
    }

    /**
     * @param string $name
     * @param null|mixed $default
     * @param int $filter
     * @param mixed $options
     * @return mixed|null
     */
    public function getServer(string $name, $default = null, int $filter = FILTER_DEFAULT, $options = null)
    {
        return $this->filterVar($name, $this->getServerParams(), $filter, $options)
            ?? $default;
    }

    /**
     * @param string $key
     * @param mixed $var
     * @param int $filter
     * @param mixed $options
     * @return mixed|null
     */
    private function filterVar(string $key, $var, int $filter, $options)
    {
        if (is_array($var) && isset($var[$key])) {
            $result = filter_var(trim($var[$key]), $filter, $options);

            if (false !== $result) {
                return $result;
            }
        }

        return null;
    }

    /**
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
}
