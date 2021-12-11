<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $schema = Capsule::schema();
        $schema->create(
            'users',
            function (Blueprint $table) {
                $table->id();
                $table->timestamps();
                $table->string('name')->nullable();
                $table->string('login')->unique()->nullable();
                $table->string('email')->unique()->nullable();
                $table->string('phone')->unique()->nullable();
                $table->string('password');
                $table->boolean('confirmed')->nullable();
                $table->boolean('email_confirmed')->nullable();
                $table->string('confirmation_code')->nullable();
                $table->tinyInteger('failed_login')->nullable();
                $table->tinyInteger('gender')->nullable();
                $table->date('birthday')->nullable();
                $table->timestamp('last_visit')->nullable();
                $table->json('settings')->nullable();
                $table->json('additional_fields')->nullable();
            }
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $schema = Capsule::schema();
        $schema->dropIfExists('users');
    }
}
