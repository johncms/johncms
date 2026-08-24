<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\UseCases;

use Johncms\Mail\Exception\InvalidEmailAddressException;
use Johncms\Mail\MailFactory;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Exception\RfcComplianceException;

/**
 * Sends one message to an address the administrator names, to find out whether the settings work.
 *
 * Deliberately not queued: the point is to see the answer of the mail server on the same screen
 * where the settings were entered, and a queued message would only report a minute later, into a
 * log file.
 */
final readonly class SendTestEmailUseCase
{
    public function __construct(private MailFactory $mailFactory)
    {
    }

    /**
     * @throws InvalidEmailAddressException
     * @throws TransportExceptionInterface When the mail server refuses the message; its own words
     *                                     are what the administrator needs to see.
     */
    public function execute(string $recipient, string $subject, string $body): void
    {
        try {
            $address = new Address(trim($recipient));
        } catch (RfcComplianceException) {
            throw InvalidEmailAddressException::forAddress($recipient);
        }

        $email = $this->mailFactory->createEmail();
        $email->to($address);
        $email->subject($subject);
        $email->text($body);

        $this->mailFactory->send($email);
    }
}
