<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddPlaceVisitToActivityTable extends Migration
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
            $table->timestamp('last_visit')->index()->after('user_id')->nullable();
            $table->string('route')->index()->after('last_visit')->nullable();
            $table->json('route_params')->after('route')->nullable();
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
            $table->dropColumn(['last_visit', 'route', 'route_params']);
        });
    }
}
