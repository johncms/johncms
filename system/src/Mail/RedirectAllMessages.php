<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Mail;

use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

/**
 * Sends every message of the site to one mailbox instead of its real recipients.
 *
 * This is what makes a copy of a live site safe to work on: a staging copy runs with the database
 * of the real one, and the first registration test would otherwise write to a real visitor. Whom
 * the message was addressed to is kept in the `X-Original-To` header, so the redirected copy still
 * shows what would have happened.
 *
 * Off unless `redirect_to` is configured.
 */
final readonly class RedirectAllMessages
{
    /**
     * @param list<Address> $recipients
     */
    public function __construct(private array $recipients)
    {
    }

    /**
     * Reads the `redirect_to` setting, which holds one address or several.
     *
     * @param array<string, mixed> $config The `mail` configuration section.
     */
    public static function fromConfig(array $config): ?self
    {
        $recipients = [];

        foreach ((array) ($config['redirect_to'] ?? []) as $address) {
            $address = trim((string) $address);
            if ($address !== '') {
                $recipients[] = new Address($address);
            }
        }

        return $recipients === [] ? null : new self($recipients);
    }

    public function __invoke(MessageEvent $event): void
    {
        $message = $event->getMessage();

        // The envelope is what the transport actually delivers to, so it has to be redirected as
        // well; changing only the headers would still send the message to the real recipient.
        $event->getEnvelope()->setRecipients($this->recipients);

        if (! $message instanceof Email) {
            return;
        }

        $original = array_map(
            static fn(Address $address): string => $address->toString(),
            [...$message->getTo(), ...$message->getCc(), ...$message->getBcc()]
        );

        if ($original !== []) {
            $message->getHeaders()->addTextHeader('X-Original-To', implode(', ', $original));
        }

        $message->to(...$this->recipients);
        $message->cc();
        $message->bcc();
    }
}
