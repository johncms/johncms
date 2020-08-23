<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

const CONSOLE_MODE = true;

require '../system/bootstrap.php';

$schema = Capsule::Schema();
$connection = Capsule::connection();

$connection->statement('ALTER TABLE cms_ads ENGINE = InnoDB');
$connection->statement('ALTER TABLE cms_album_cat ENGINE = InnoDB');
$connection->statement('ALTER TABLE cms_album_comments ENGINE = InnoDB');
$connection->statement('ALTER TABLE cms_album_downloads ENGINE = InnoDB');
$connection->statement('ALTER TABLE cms_album_files ENGINE = InnoDB');
$connection->statement('ALTER TABLE cms_album_views ENGINE = InnoDB');
$connection->statement('ALTER TABLE cms_album_votes ENGINE = InnoDB');
$connection->statement('ALTER TABLE cms_ban_ip ENGINE = InnoDB');
$connection->statement('ALTER TABLE cms_ban_users ENGINE = InnoDB');
$connection->statement('ALTER TABLE cms_contact ENGINE = InnoDB');
$connection->statement('ALTER TABLE cms_counters ENGINE = InnoDB');
$connection->statement('ALTER TABLE cms_forum_files ENGINE = InnoDB');
$connection->statement('ALTER TABLE cms_forum_rdm ENGINE = InnoDB');
$connection->statement('ALTER TABLE cms_forum_vote ENGINE = InnoDB');
$connection->statement('ALTER TABLE cms_forum_vote_users ENGINE = InnoDB');
$connection->statement('ALTER TABLE cms_library_comments ENGINE = InnoDB');
$connection->statement('ALTER TABLE cms_library_rating ENGINE = InnoDB');
$connection->statement('ALTER TABLE cms_mail ENGINE = InnoDB');
$connection->statement('ALTER TABLE cms_sessions ENGINE = InnoDB');
$connection->statement('ALTER TABLE cms_users_data ENGINE = InnoDB');
$connection->statement('ALTER TABLE cms_users_guestbook ENGINE = InnoDB');
$connection->statement('ALTER TABLE cms_users_iphistory ENGINE = InnoDB');
$connection->statement('ALTER TABLE download__bookmark ENGINE = InnoDB');
$connection->statement('ALTER TABLE download__category ENGINE = InnoDB');
$connection->statement('ALTER TABLE download__comments ENGINE = InnoDB');
$connection->statement('ALTER TABLE download__files ENGINE = InnoDB');
$connection->statement('ALTER TABLE download__more ENGINE = InnoDB');
$connection->statement('ALTER TABLE guest ENGINE = InnoDB');
$connection->statement('ALTER TABLE karma_users ENGINE = InnoDB');
$connection->statement('ALTER TABLE library_cats ENGINE = InnoDB');
$connection->statement('ALTER TABLE library_tags ENGINE = InnoDB');
$connection->statement('ALTER TABLE library_texts ENGINE = InnoDB');
$connection->statement('ALTER TABLE news ENGINE = InnoDB');

$schema->table(
    'forum_sections',
    static function (Blueprint $table) use ($schema) {
        if (! $schema->hasColumns('forum_sections', ['meta_description', 'meta_keywords'])) {
            $table->text('meta_description')->nullable()->after('description');
            $table->string('meta_keywords')->nullable()->after('meta_description');
        }
        if ($schema->hasColumns('forum_sections', ['old_id'])) {
            $table->dropColumn(['old_id']);
        }
    }
);

$schema->table(
    'forum_topic',
    static function (Blueprint $table) use ($schema) {
        if (! $schema->hasColumns('forum_topic', ['meta_description', 'meta_keywords'])) {
            $table->text('meta_description')->nullable()->after('description');
            $table->string('meta_keywords')->nullable()->after('meta_description');
        }
        if ($schema->hasColumns('forum_topic', ['old_id'])) {
            $table->dropColumn(['old_id']);
        }
    }
);

if ($schema->hasColumns('forum_messages', ['old_id'])) {
    $schema->table(
        'forum_messages',
        static function (Blueprint $table) {
            $table->dropColumn(['old_id']);
        }
    );
}

$schema->dropIfExists('forum_redirects');

echo 'Update complete!';
