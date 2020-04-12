<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

require '../system/bootstrap.php';

Capsule::Schema()->table(
    'cms_forum_vote',
    static function (Blueprint $table) {
        $table->index(['type', 'topic'], 'type_topic');
    }
);

Capsule::Schema()->table(
    'cms_forum_vote_users',
    static function (Blueprint $table) {
        $table->index(['topic', 'user'], 'topic_user');
    }
);

echo 'Update complete!';
