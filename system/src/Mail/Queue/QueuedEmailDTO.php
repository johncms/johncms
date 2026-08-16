<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Mail\Queue;

/**
 * One message on its way into the queue.
 *
 * What used to be an untyped array written out at each call site: the recipient, the subject and
 * the variables of the template all lived in the same bag, and a misspelled key was found by the
 * cron a minute later, if at all.
 */
final readonly class QueuedEmailDTO
{
    /**
     * @param string $template Twig template of the message body.
     * @param string $locale Language the message is written in — the language of its recipient.
     * @param string $recipient Address the message goes to.
     * @param string $recipientName Display name of the recipient, if there is a usable one.
     * @param string $subject Subject line.
     * @param int $priority Lower goes out first.
     * @param array<string, mixed> $variables Values the template reads.
     * @param string $replyTo Where an answer should go, when it is not the address of the site.
     * @param string $replyToName Display name of the reply-to address.
     * @param list<string> $cc
     * @param list<string> $bcc
     */
    public function __construct(
        public string $template,
        public string $locale,
        public string $recipient,
        public string $recipientName = '',
        public string $subject = '',
        public int $priority = 100,
        public array $variables = [],
        public string $replyTo = '',
        public string $replyToName = '',
        public array $cc = [],
        public array $bcc = [],
    ) {
    }

    /**
     * The row of the queue, in the shape the sender reads.
     *
     * @return array<string, mixed>
     */
    public function fields(): array
    {
        $fields = [
            'email_to' => $this->recipient,
            'name_to'  => $this->recipientName,
            'subject'  => $this->subject,
        ];

        if ($this->replyTo !== '') {
            $fields['reply_to'] = $this->replyTo;
            $fields['reply_to_name'] = $this->replyToName;
        }

        if ($this->cc !== []) {
            $fields['cc'] = $this->cc;
        }

        if ($this->bcc !== []) {
            $fields['bcc'] = $this->bcc;
        }

        // The variables come last so that a template variable can never overwrite an address.
        return $fields + $this->variables;
    }
}
