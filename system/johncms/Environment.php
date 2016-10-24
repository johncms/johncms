<?php

namespace Johncms;

use Interop\Container\ContainerInterface;

class Environment
{
    private $ip;
    private $ipViaProxy;
    private $userAgent;

    /**
     * @var ContainerInterface
     */
    private $container;

    public function __invoke(ContainerInterface $container)
    {
        $this->container = $container;

        return $this;
    }

    public function getIp()
    {
        return null === $this->ip
            ? sprintf("%u", ip2long(filter_input(INPUT_SERVER, 'REMOTE_ADDR', FILTER_VALIDATE_IP)))
            : $this->ip;
    }

    public function getIpViaProxy()
    {
        if ($this->ipViaProxy !== null) {
            return $this->ipViaProxy;
        } elseif (filter_has_var(INPUT_SERVER, 'HTTP_X_FORWARDED_FOR')
            && preg_match_all(
                '#\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}#s',
                filter_input(INPUT_SERVER, 'REMOTE_ADDR', FILTER_SANITIZE_STRING),
                $vars
            )
        ) {
            foreach ($vars[0] AS $var) {
                $ipViaProxy = ip2long($var);

                if ($ipViaProxy && $ipViaProxy != $this->getIp() && !preg_match('#^(10|172\.16|192\.168)\.#', $var)) {
                    return $this->ipViaProxy = sprintf("%u", $ipViaProxy);
                }
            }
        }

        return $this->ipViaProxy = 0;
    }

    public function getUserAgent()
    {
        if ($this->userAgent !== null) {
            return $this->userAgent;
        } elseif (filter_has_var(INPUT_SERVER, 'HTTP_X_OPERAMINI_PHONE_UA') && strlen(trim($_SERVER['HTTP_X_OPERAMINI_PHONE_UA'])) > 5) {
            return $this->userAgent = 'Opera Mini: ' . mb_substr(filter_input(INPUT_SERVER, 'HTTP_X_OPERAMINI_PHONE_UA', FILTER_SANITIZE_SPECIAL_CHARS), 0, 150);
        } elseif (filter_has_var(INPUT_SERVER, 'HTTP_USER_AGENT')) {
            return $this->userAgent = mb_substr(filter_input(INPUT_SERVER, 'HTTP_USER_AGENT', FILTER_SANITIZE_SPECIAL_CHARS), 0, 150);
        } else {
            return $this->userAgent = 'Not Recognised';
        }
    }
}
