<?php

declare(strict_types=1);

namespace Johncms\Modules\Contacts\Install;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

class Installer extends \Johncms\Modules\Installer
{
    public function install(): void
    {
        $this->createTables();
    }

    public function uninstall(): void
    {
        Capsule::schema()->dropIfExists('contact_messages');
    }

    private function createTables(): void
    {
        Capsule::schema()->create(
            'contact_messages',
            static function (Blueprint $table): void {
                $table->increments('id');
                $table->integer('user_id')->unsigned()->nullable()->index();
                $table->string('name');
                $table->string('email');
                $table->text('message');
                $table->string('status', 20)->default('new')->index();
                $table->string('ip_address', 45);
                $table->string('user_agent')->nullable();
                $table->dateTime('processed_at')->nullable();
                $table->timestamps();
            }
        );
    }
}
