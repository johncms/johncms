<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

const CONSOLE_MODE = true;

require '../system/bootstrap.php';

$schema = Capsule::Schema();

$schema->table(
    'cms_forum_vote',
    static function (Blueprint $table) {
        $table->index(['type', 'topic'], 'type_topic');
    }
);

$schema->table(
    'cms_forum_vote_users',
    static function (Blueprint $table) {
        $table->index(['topic', 'user'], 'topic_user');
    }
);

if (! $schema->hasTable('email_messages')) {
    $schema->create(
        'email_messages',
        static function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('priority')->nullable()->comment('Priority of sending the message');
            $table->string('locale', 8)->comment('The language used for displaying the message');
            $table->string('template')->comment('Template name');
            $table->text('fields')->nullable()->comment('Event fields');
            $table->timestamp('sent_at')->nullable()->comment('The time when the message was sent');
            $table->timestamps();
        }
    );
}

if (! $schema->hasColumns('users', ['email_confirmed', 'confirmation_code'])) {
    $schema->table(
        'users',
        static function (Blueprint $table) {
            $table->boolean('email_confirmed')->nullable();
            $table->string('confirmation_code', 50)->nullable();
            $table->string('new_email', 50)->nullable()->comment('New email address waiting for confirmation');
            $table->text('admin_notes')->nullable()->comment('Admin Notes');
        }
    );
}

/** @var PDO $db */
$db = di(PDO::class);
$db->query('UPDATE users SET email_confirmed = 1');

echo 'Update complete!';
