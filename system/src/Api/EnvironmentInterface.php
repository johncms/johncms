<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Api;

interface EnvironmentInterface
{
    public function getIp() : int;

    public function getIpViaProxy() : int;

    public function getUserAgent() : string;

    public function getIpLog() : array;
}
