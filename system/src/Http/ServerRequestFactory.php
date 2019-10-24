<?php

declare(strict_types=1);

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

namespace Johncms\Http;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\ServerRequestFactory as Factory;

/**
 * Class ServerRequestFactory
 *
 * @package Mobicms\Http
 */
class ServerRequestFactory
{
    /**
     * @var array HTTP headers to determine the client User Agent
     */
    private $uaCheckHeaders = [
        'X-OperaMini-Phone-UA',
        'User-Agent',
    ];

    public function __invoke(ContainerInterface $container)
    {
        $config = $container->get('config');
        $request = Factory::fromGlobals();

        if (!empty($config['mobicms']['base_path'])) {
            $request = $this->normalizeBasePath($request, $config['mobicms']['base_path']);
        }

        $request = $this->determineIp($request);
        $request = $this->determineIpViaProxy($request);
        $request = $this->determineUserAgent($request);

        return $request;
    }

    /**
     * Determine the client IP address and stores it as an ServerRequest attribute
     *
     * @param ServerRequestInterface $request
     * @return ServerRequestInterface
     */
    private function determineIp(ServerRequestInterface $request)
    {
        $serverParams = $request->getServerParams();
        $ipAddress = isset($serverParams['REMOTE_ADDR']) && $this->isValidIpAddress($serverParams['REMOTE_ADDR'])
            ? $serverParams['REMOTE_ADDR']
            : null;

        return $request->withAttribute('ip', $ipAddress);
    }

    /**
     * Determine the client IP via Proxy address and stores it as an ServerRequest attribute
     *
     * @param ServerRequestInterface $request
     * @return ServerRequestInterface
     */
    private function determineIpViaProxy(ServerRequestInterface $request)
    {
        $ipAddress = '';

        if ($request->hasHeader('X-Forwarded-For')
            && preg_match_all('#\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}#s',
                $request->getHeaderLine('X-Forwarded-For'),
                $vars
            )
        ) {
            foreach ($vars[0] AS $var) {
                if ($this->isValidIpAddress($var)
                    && $var != $request->getAttribute('ip')
                    && !preg_match('#^(10|172\.16|192\.168)\.#', $var)
                ) {
                    $ipAddress = $var;
                    break;
                }
            }
        }

        return $request->withAttribute('ip_via_proxy', $ipAddress);
    }

    /**
     * Determine the client User Agent and stores it as an ServerRequest attribute
     *
     * @param ServerRequestInterface $request
     * @return ServerRequestInterface
     */
    private function determineUserAgent(ServerRequestInterface $request)
    {
        $userAgent = 'Not Recognised';

        foreach ($this->uaCheckHeaders as $val) {
            if ($request->hasHeader($val)) {
                $userAgent = filter_var($request->getHeaderLine($val), FILTER_SANITIZE_STRING);
                break;
            }
        }

        return $request->withAttribute('user_agent', $userAgent);
    }

    /**
     * Remove a path prefix from a request uri
     *
     * @param ServerRequestInterface $request
     * @param string                 $basePath
     * @return ServerRequestInterface
     */
    private function normalizeBasePath(ServerRequestInterface $request, $basePath)
    {
        $basePath = '/' . trim($basePath, '/');
        $uri = $request->getUri();
        $path = substr($uri->getPath(), strlen($basePath)) ?: '/';

        return $request->withUri($uri->withPath($path));
    }

    /**
     * Check that a given string is a valid IP address
     *
     * @param  string $ip
     * @return boolean
     */
    private function isValidIpAddress($ip)
    {
        $flags = FILTER_FLAG_IPV4;

        return filter_var($ip, FILTER_VALIDATE_IP, $flags) === false ?: true;
    }
}
