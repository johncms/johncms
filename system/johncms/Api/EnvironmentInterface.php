<?php

declare(strict_types=1);

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
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
