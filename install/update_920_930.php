<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

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
            $table->string('locale', 8)->comment('The language used for displaying the message.');
            $table->string('template')->comment('Template name');
            $table->text('fields')->nullable()->comment('Event fields');
            $table->timestamp('sent_at')->nullable()->comment('The time when the message was sent');
            $table->timestamps();
        }
    );
}

echo 'Update complete!';
