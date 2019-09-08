<?php
/*
 * JohnCMS NEXT Mobile Content Management System (http://johncms.com)
 *
 * For copyright and license information, please see the LICENSE.md
 * Installing the system or redistributions of files must retain the above copyright notice.
 *
 * @link        http://johncms.com JohnCMS Project
 * @copyright   Copyright (C) JohnCMS Community
 * @license     GPL-3
 */

namespace Johncms\Api;

/**
 * Interface EnvironmentInterface
 *
 * @package Johncms\Api
 */
interface EnvironmentInterface
{
    public function getIp();

    public function getIpViaProxy();

    public function getUserAgent();

    public function getIpLog();
}