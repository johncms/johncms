<?php

namespace Johncms;

use Interop\Container\ContainerInterface;

class VarsFactory
{
    public function __invoke(ContainerInterface $container)
    {
        return $this;
    }

    public function getIp()
    {
        return sprintf("%u", ip2long(filter_input(INPUT_SERVER, 'REMOTE_ADDR', FILTER_VALIDATE_IP)));
    }

    public function getIpViaProxy()
    {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && preg_match_all('#\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}#s', $_SERVER['HTTP_X_FORWARDED_FOR'], $vars)) {
            foreach ($vars[0] AS $var) {
                $ip_via_proxy = ip2long($var);

                if ($ip_via_proxy && $ip_via_proxy != $this->getIp() && !preg_match('#^(10|172\.16|192\.168)\.#', $var)) {
                    return sprintf("%u", $ip_via_proxy);
                }
            }
        }

        return 0;
    }

    public function getUserAgent()
    {
        if (isset($_SERVER["HTTP_X_OPERAMINI_PHONE_UA"]) && strlen(trim($_SERVER['HTTP_X_OPERAMINI_PHONE_UA'])) > 5) {
            return 'Opera Mini: ' . htmlspecialchars(mb_substr(trim($_SERVER['HTTP_X_OPERAMINI_PHONE_UA']), 0, 150));
        } elseif (isset($_SERVER['HTTP_USER_AGENT'])) {
            return htmlspecialchars(mb_substr(trim($_SERVER['HTTP_USER_AGENT']), 0, 150));
        } else {
            return 'Not Recognised';
        }
    }
}
