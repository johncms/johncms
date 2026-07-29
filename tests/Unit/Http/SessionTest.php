<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use Johncms\Http\Session;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Tests for the per-request reset of the shared session facade.
 */
final class SessionTest extends TestCase
{
    public function testResetLeavesTheSessionOfTheRequestBeingServedAlone(): void
    {
        // Under FPM the boot opens the session before the kernel resets anything, so an open
        // session is the current one. Dropping its bags here would lose what the request wrote.
        $session = new Session(new MockArraySessionStorage());
        $session->set('key', 'value');

        $session->reset();

        self::assertSame('value', $session->get('key'));
    }

    public function testAFlashOfTheClosedSessionIsNotDeliveredToTheNextVisitor(): void
    {
        // Flashes are the loudest kind of leak: a message meant for one visitor showing up on
        // somebody else's page. They live in a bag of their own, which reset() drops as well.
        $storage = new MockArraySessionStorage();
        $session = new Session($storage);
        $session->flash('message', 'for visitor one');
        $session->save();

        $session->reset();
        $storage->setSessionData(['_sf2_attributes' => ['key' => 'visitor-two']]);

        self::assertNull($session->getFlash('message'));
    }

    public function testTheSessionThatCannotBeReopenedReadsAsEmptyRatherThanAsSomebodyElse(): void
    {
        // Native storage refuses to reopen a session once the response headers are out. Whatever
        // that does to a worker cycle, it must not end with the previous visitor's data being
        // served: the read fails instead of falling back to the bags left from the last request.
        $storage = new class () extends MockArraySessionStorage {
            public bool $refuseStart = false;

            public function start(): bool
            {
                if ($this->refuseStart) {
                    // What native storage does once the response headers are out.
                    throw new RuntimeException('Failed to start the session.');
                }

                return parent::start();
            }
        };
        $session = new Session($storage);
        $session->set('secret', 'visitor-one');
        $session->save();

        $session->reset();
        $storage->refuseStart = true;

        $this->expectException(RuntimeException::class);
        $session->get('secret');
    }

    public function testTheFacadeKeepsWorkingAfterReset(): void
    {
        $storage = new MockArraySessionStorage();
        $session = new Session($storage);
        $session->set('key', 'visitor-one');
        $session->save();

        $session->reset();
        $storage->setSessionData(['_sf2_attributes' => ['key' => 'visitor-two']]);

        self::assertSame('visitor-two', $session->get('key'));
    }
}
