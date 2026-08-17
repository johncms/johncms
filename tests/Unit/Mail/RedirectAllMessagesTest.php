<?php

declare(strict_types=1);

namespace Tests\Unit\Mail;

use Johncms\Mail\RedirectAllMessages;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

/**
 * The safety net of a staging copy: it runs with the database of the live site, so nothing may
 * reach the addresses in it.
 */
final class RedirectAllMessagesTest extends TestCase
{
    private function message(): Email
    {
        return (new Email())
            ->from('site@example.com')
            ->to('visitor@example.com')
            ->cc('boss@example.com')
            ->bcc('audit@example.com')
            ->subject('Notice')
            ->text('body');
    }

    private function dispatch(RedirectAllMessages $redirect, Email $message): MessageEvent
    {
        $envelope = new Envelope(new Address('site@example.com'), $message->getTo());
        $event = new MessageEvent($message, $envelope, 'smtp');

        $redirect($event);

        return $event;
    }

    public function testEveryRecipientIsReplaced(): void
    {
        $redirect = RedirectAllMessages::fromConfig(['redirect_to' => 'dev@example.com']);
        self::assertNotNull($redirect);

        $message = $this->message();
        $event = $this->dispatch($redirect, $message);

        self::assertSame(['dev@example.com'], $this->addresses($message->getTo()));
        self::assertSame([], $message->getCc());
        self::assertSame([], $message->getBcc());

        // The envelope is what the transport delivers to; leaving it alone would still reach the
        // real recipient.
        self::assertSame(['dev@example.com'], $this->addresses($event->getEnvelope()->getRecipients()));
    }

    public function testTheOriginalRecipientsAreRecorded(): void
    {
        $redirect = RedirectAllMessages::fromConfig(['redirect_to' => 'dev@example.com']);
        $message = $this->message();

        $this->dispatch($redirect, $message);

        $header = $message->getHeaders()->get('X-Original-To')?->getBodyAsString();
        self::assertNotNull($header);
        self::assertStringContainsString('visitor@example.com', $header);
        self::assertStringContainsString('boss@example.com', $header);
        self::assertStringContainsString('audit@example.com', $header);
    }

    public function testSeveralMailboxesCanReceiveTheRedirectedMail(): void
    {
        $redirect = RedirectAllMessages::fromConfig(
            ['redirect_to' => ['dev@example.com', '  ', 'qa@example.com']]
        );

        $message = $this->message();
        $this->dispatch($redirect, $message);

        self::assertSame(['dev@example.com', 'qa@example.com'], $this->addresses($message->getTo()));
    }

    /**
     * A live site must not be redirecting anything by accident.
     */
    public function testRedirectionIsOffUnlessItIsConfigured(): void
    {
        self::assertNull(RedirectAllMessages::fromConfig([]));
        self::assertNull(RedirectAllMessages::fromConfig(['redirect_to' => '']));
        self::assertNull(RedirectAllMessages::fromConfig(['redirect_to' => ['', '   ']]));
    }

    /**
     * @param list<Address> $addresses
     * @return list<string>
     */
    private function addresses(array $addresses): array
    {
        return array_map(static fn(Address $address): string => $address->getAddress(), $addresses);
    }
}
