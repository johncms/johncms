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

use Johncms\Mail\Queue\EmailQueueInterface;
use Johncms\Mail\Queue\MailQueueRunDTO;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mime\Address;
use Throwable;

/**
 * Delivers one batch of the mail queue.
 *
 * Two kinds of failure are told apart, because treating them alike loses mail either way. A
 * transport that is down is temporary: the message keeps its place and is tried again later. A
 * message that cannot be built at all — no recipient, an address no server would accept, a
 * template that does not render — will fail identically on every future run, so it is given up on
 * at once instead of holding up the queue forever.
 */
final readonly class EmailSender
{
    public function __construct(
        private EmailQueueInterface $queue,
        private MailRenderer $renderer,
        private MailFactory $mailFactory,
        private LoggerInterface $logger,
    ) {
    }

    public function send(int $messageCount = 5): MailQueueRunDTO
    {
        $sent = 0;
        $retrying = 0;
        $failed = 0;

        foreach ($this->queue->claim($messageCount) as $message) {
            $fields = is_array($message->fields) ? $message->fields : [];

            $recipient = trim((string) ($fields['email_to'] ?? ''));
            if ($recipient === '' || $message->template === '') {
                $this->giveUp($message, 'The message has no recipient or no template.');
                $failed++;
                continue;
            }

            try {
                $email = $this->mailFactory->createEmail();
                $email->to($this->address($recipient, (string) ($fields['name_to'] ?? '')));

                $subject = trim((string) ($fields['subject'] ?? ''));
                if ($subject !== '') {
                    $email->subject($subject);
                }

                $email->html(
                    $this->renderer->render(self::template($message->template), $fields, (string) $message->locale)
                );
            } catch (Throwable $exception) {
                // Building the message is deterministic: whatever went wrong here — an address no
                // server would accept (RfcComplianceException), a template that does not render —
                // goes wrong the same way on every future run.
                $this->giveUp($message, $exception->getMessage(), $exception);
                $failed++;
                continue;
            }

            try {
                $this->mailFactory->send($email);
            } catch (Throwable $exception) {
                // A transport that refuses the message right now (TransportExceptionInterface) may
                // well accept it later, so the message keeps its place until the attempts run out.
                $willRetry = $this->queue->markAttemptFailed($message, $exception->getMessage());

                $this->logger->error(
                    sprintf('[EmailSender] Failed to send email to %s', $recipient),
                    [
                        'message_id' => $message->id,
                        'attempts'   => $message->attempts + 1,
                        'will_retry' => $willRetry,
                        'exception'  => $exception,
                    ]
                );

                $willRetry ? $retrying++ : $failed++;
                continue;
            }

            $this->queue->markSent($message);
            $sent++;
        }

        return new MailQueueRunDTO(sent: $sent, retrying: $retrying, failed: $failed);
    }

    /**
     * An address the mailer will accept, with the display name when there is a usable one.
     *
     * The name is dropped when it holds an `@`: such a name used to break sending outright, and
     * a message that arrives without a display name is better than one that does not arrive.
     */
    private function address(string $email, string $name): Address
    {
        $name = trim($name);
        if ($name === '' || str_contains($name, '@')) {
            return new Address($email);
        }

        return new Address($email, $name);
    }

    private function giveUp(EmailMessage $message, string $error, ?Throwable $exception = null): void
    {
        $this->queue->markFailed($message, $error);

        $this->logger->error(
            '[EmailSender] Gave up on an email that cannot be built',
            [
                'message_id' => $message->id,
                'template'   => $message->template,
                'reason'     => $error,
                'exception'  => $exception,
            ]
        );
    }

    /**
     * A row queued before the templates moved to Twig still names a Plates template; it is sent
     * with the Twig one of the same name.
     */
    private static function template(string $template): string
    {
        if (! str_starts_with($template, 'system::mail/templates/')) {
            return $template;
        }

        $name = substr($template, strlen('system::mail/templates/'));

        return '@theme/emails/' . str_replace('_', '-', $name) . '.twig';
    }
}
