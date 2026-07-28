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

use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;
use Symfony\Component\HttpFoundation\Session\Storage\SessionStorageInterface;

/**
 * Builds the session facade with the storage matching the current runtime.
 *
 * Console commands, cron jobs and the functional test harness must never open a real PHP
 * session: any read through the facade would call session_start() and leave a sess_* file
 * behind on every run. In-memory storage keeps the facade fully usable there and writes
 * nothing to disk.
 */
final class SessionFactory
{
    private const SESSION_NAME = 'SESID';

    public function __invoke(): Session
    {
        return new Session($this->createStorage());
    }

    private function createStorage(): SessionStorageInterface
    {
        if (defined('CONSOLE_MODE') && CONSOLE_MODE === true) {
            return new MockArraySessionStorage(self::SESSION_NAME);
        }

        return new NativeSessionStorage(['name' => self::SESSION_NAME]);
    }
}
