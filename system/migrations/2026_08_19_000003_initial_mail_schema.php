<?php

/**
 * The mail queue as it stood when migrations were introduced. Part of the baseline: it creates
 * what is missing and leaves the rest alone.
 */

declare(strict_types=1);

use Johncms\Database\Migrations\Migration;
use Johncms\Database\Schema\TableDefinition;

return new class extends Migration {
    public function up(): void
    {
        $this->createEmailMessages();
        $this->addDeliveryTracking();
    }

    private function createEmailMessages(): void
    {
        if ($this->schema->hasTable('email_messages')) {
            return;
        }

        $this->schema->create('email_messages', function (TableDefinition $table): void {
            $table->bigIncrements('id');
            $table->integer('priority')->nullable()->comment('Priority of sending the message');
            $table->string('locale', 8)->comment('The language used for displaying the message');
            $table->string('template')->comment('Template name');
            $table->text('fields')->nullable()->comment('Event fields');
            $table->timestamp('sent_at')->nullable()->comment('The time when the message was sent');
            $table->timestamps();
            $this->defineDeliveryTracking($table);
            $this->defineQueueIndex($table);
        });
    }

    /**
     * Carries a table created before delivery was tracked: without these columns a message that
     * could not be sent is lost instead of being tried again.
     */
    private function addDeliveryTracking(): void
    {
        if (! $this->schema->hasTable('email_messages')) {
            return;
        }

        if ($this->schema->hasColumn('email_messages', 'attempts')) {
            return;
        }

        $this->schema->alter('email_messages', function (TableDefinition $table): void {
            $this->defineDeliveryTracking($table);
            $this->defineQueueIndex($table);
        });
    }

    private function defineDeliveryTracking(TableDefinition $table): void
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
    private function defineQueueIndex(TableDefinition $table): void
    {
        $table->index(['sent_at', 'failed_at', 'priority'], 'email_messages_queue_index');
    }
};
