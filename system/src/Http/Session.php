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

use Symfony\Component\HttpFoundation\Session\Session as SymfonySession;
use Symfony\Component\HttpFoundation\Session\Storage\SessionStorageInterface;

/**
 * Application-level session facade over Symfony's session.
 *
 * Keys are flat: dot notation (`a.b.c`) is not resolved, nested data is read and written as
 * whole arrays (see Johncms\Security\Csrf).
 *
 * The storage is chosen by SessionFactory, not here. Reading any key starts the session when
 * it is not started yet, which is why the web bootstrap starts it explicitly before anything
 * else touches the facade — where a session opens must not depend on resolution order.
 */
class Session
{
    private SymfonySession $symfonySession;

    public function __construct(SessionStorageInterface $storage)
    {
        $this->symfonySession = new SymfonySession($storage);
    }

    /**
     * Flash a value for the next request (single-read then delete).
     */
    public function flash(string $key, mixed $value): void
    {
        // The flash bag stores a list of messages per key. Wrapping in an array keeps array
        // values intact — FlashBag::set() casts a bare value with (array), which would flatten
        // an array payload into several messages.
        $this->symfonySession->getFlashBag()->set($key, [$value]);
    }

    /**
     * Read and delete a flashed value.
     */
    public function getFlash(string $key): mixed
    {
        $messages = $this->symfonySession->getFlashBag()->get($key);

        return $messages[0] ?? null;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->symfonySession->get($key, $default);
    }

    public function set(string $key, mixed $value): void
    {
        $this->symfonySession->set($key, $value);
    }

    public function has(string $key): bool
    {
        return $this->symfonySession->has($key);
    }

    /**
     * @param array<int, string>|string $key
     */
    public function remove(array|string $key): void
    {
        foreach ((array) $key as $item) {
            $this->symfonySession->remove($item);
        }
    }

    /**
     * Clear all session data (attributes + flash), keeping the session id.
     */
    public function clear(): void
    {
        $this->symfonySession->clear();
        $this->symfonySession->getFlashBag()->clear();
    }

    /**
     * Clear all session data and issue a new session id. Use this on logout: keeping the id
     * would leave the pre-logout identifier valid for whoever else knows it (session fixation).
     */
    public function invalidate(): void
    {
        $this->clear();
        $this->symfonySession->migrate(true);
    }

    /**
     * Start the session. Idempotent: does nothing when the session is already started.
     */
    public function start(): void
    {
        if (! $this->symfonySession->isStarted()) {
            $this->symfonySession->start();
        }
    }

    /**
     * Whether the session has been started.
     */
    public function isStarted(): bool
    {
        return $this->symfonySession->isStarted();
    }

    /**
     * Write session data and close the session.
     */
    public function save(): void
    {
        $this->symfonySession->save();
    }
}
