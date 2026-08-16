<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Mail\Schema;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

/**
 * The table of the mail queue, defined once.
 *
 * Three callers need it and must not disagree: the installer building a fresh site, the command
 * upgrading an existing one, and the tests.
 *
 * Every method is safe to call again: it creates what is missing and leaves the rest alone.
 */
final class MailSchema
{
    public const EMAIL_MESSAGES = 'email_messages';

    public static function create(Builder $schema): void
    {
        self::createEmailMessages($schema);
        self::addDeliveryTracking($schema);
    }

    private static function createEmailMessages(Builder $schema): void
    {
        if ($schema->hasTable(self::EMAIL_MESSAGES)) {
            return;
        }

        $schema->create(
            self::EMAIL_MESSAGES,
            static function (Blueprint $table): void {
                $table->bigIncrements('id');
                $table->integer('priority')->nullable()->comment('Priority of sending the message');
                $table->string('locale', 8)->comment('The language used for displaying the message');
                $table->string('template')->comment('Template name');
                $table->text('fields')->nullable()->comment('Event fields');
                $table->timestamp('sent_at')->nullable()->comment('The time when the message was sent');
                $table->timestamps();

                self::defineDeliveryTracking($table);
                self::defineQueueIndex($table);
            }
        );
    }

    /**
     * Carries a table created before delivery was tracked.
     */
    private static function addDeliveryTracking(Builder $schema): void
    {
        if (! $schema->hasTable(self::EMAIL_MESSAGES)) {
            return;
        }

        if ($schema->hasColumn(self::EMAIL_MESSAGES, 'attempts')) {
            return;
        }

        $schema->table(
            self::EMAIL_MESSAGES,
            static function (Blueprint $table): void {
                self::defineDeliveryTracking($table);
                self::defineQueueIndex($table);
            }
        );
    }

    private static function defineDeliveryTracking(Blueprint $table): void
    {
        // How many times delivery was tried. A message is given up on once it reaches the limit
        // of the configuration.
        $table->integer('attempts')->unsigned()->default(0);
        // Why the last attempt did not work, kept for whoever reads the queue afterwards.
        $table->text('last_error')->nullable();
        // Not before this moment: what spaces the retries out. Null means "as soon as possible".
        $table->timestamp('available_at')->nullable();
        // Given up on: never tried again, and never silently reported as delivered either.
        $table->timestamp('failed_at')->nullable();
        // Claimed by a worker at this moment. A claim older than the lock timeout is treated as
        // abandoned, so a process killed mid-send does not park its messages forever.
        $table->timestamp('locked_at')->nullable();
        // Which worker holds the claim; this is what lets a worker read back its own batch.
        $table->string('locked_by', 64)->nullable();
    }

    /**
     * Covers the query that picks the next batch: the pending rows, oldest priority first.
     */
    private static function defineQueueIndex(Blueprint $table): void
    {
        $table->index(['sent_at', 'failed_at', 'priority'], 'email_messages_queue_index');
    }
}
