<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddIpUaToActivityTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $schema = Capsule::schema();
        $schema->table('user_activity', function (Blueprint $table) {
            $table->timestamp('session_started')->after('last_visit')->nullable();
            $table->string('ip')->after('session_started')->nullable();
            $table->string('ip_via_proxy')->after('ip')->nullable();
            $table->string('user_agent')->after('ip_via_proxy')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $schema = Capsule::schema();
        $schema->table('user_activity', function (Blueprint $table) {
            $table->dropColumn(['ip', 'ip_via_proxy', 'user_agent', 'session_started']);
        });
    }
}
