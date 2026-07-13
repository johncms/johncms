<?php

declare(strict_types=1);

namespace Johncms\Modules\Consent\Install;

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
        $schema = Capsule::schema();
        $schema->dropIfExists('consent_log');
        $schema->dropIfExists('consents');
    }

    private function createTables(): void
    {
        $schema = Capsule::schema();

        $schema->create(
            'consents',
            static function (Blueprint $table): void {
                $table->increments('id');
                $table->string('context')->index();
                $table->string('language', 5)->index();
                // The title is shown next to the checkbox and may contain inline HTML with links.
                $table->text('title');
                $table->longText('text');
                $table->string('version');
                $table->boolean('is_required')->default(true);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            }
        );

        $schema->create(
            'consent_log',
            static function (Blueprint $table): void {
                $table->increments('id');
                $table->integer('user_id')->unsigned()->nullable()->index();
                $table->integer('consent_id')->unsigned()->index();
                $table->string('version');
                $table->string('ip_address', 45);
                $table->dateTime('accepted_at');
            }
        );
    }
}
