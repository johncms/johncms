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

/**
 * The names of the tables the mail queue keeps its data in. What they look like is decided by the
 * migrations.
 */
final class MailTables
{
    public const string EMAIL_MESSAGES = 'email_messages';
}
