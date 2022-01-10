<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddGuestbookPostsColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $schema = Capsule::schema();
        if (! $schema->hasColumn('user_activity', 'guestbook_posts')) {
            $schema->table('user_activity', function (Blueprint $table) {
                $table->bigInteger('guestbook_posts')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $schema = Capsule::schema();
        if ($schema->hasColumn('user_activity', 'guestbook_posts')) {
            $schema->dropColumns('user_activity', ['guestbook_posts']);
        }
    }
}
