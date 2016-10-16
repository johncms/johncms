<?php

namespace Johncms;

use Interop\Container\ContainerInterface;

class VarsFactory
{
    public function __invoke(ContainerInterface $container)
    {
        return $this;
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
