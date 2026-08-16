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

use Johncms\Mail\Exception\InvalidEmailAddressException;

/**
 * What the rest of the site uses to send mail: a message is queued and delivered by the cron, so
 * a slow mail server never holds up a page.
 *
 * Kept apart from EmailQueueInterface on purpose — putting a message in is what modules do, taking
 * messages out is what the sender does, and neither needs the methods of the other.
 */
interface MailQueueInterface
{
    /**
     * Put a message in the queue.
     *
     * @throws InvalidEmailAddressException When an address of the message is not one a mail server
     *                                      would accept. Queuing it would only be found out by the
     *                                      cron, once the delivery attempts had run out.
     */
    public function push(QueuedEmailDTO $email): void;
}
