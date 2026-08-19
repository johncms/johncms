<?php

/**
 * The core tables as they stood when migrations were introduced.
 *
 * A snapshot, not a step: it is what an installation of 10.0 starts from. Unlike every migration
 * after it, it creates only what is missing, because a site upgrading from 9.9 already has most
 * of it and has to arrive at the same schema as a fresh one.
 */

declare(strict_types=1);

use Johncms\Database\Migrations\Migration;
use Johncms\Database\Schema\TableDefinition;

return new class extends Migration {
    public function up(): void
    {
        $this->createCmsAds();
        $this->createCmsBanIp();
        $this->createCmsBanUsers();
        $this->createCmsCounters();
        $this->createCmsSessions();
        $this->createCmsUsersData();
        $this->createCmsUsersGuestbook();
        $this->createCmsUsersIphistory();
        $this->createKarmaUsers();
        $this->createUsers();
        $this->createFiles();
    }

    private function createCmsAds(): void
    {
        if ($this->schema->hasTable('cms_ads')) {
            return;
        }

        $this->schema->create('cms_ads', static function (TableDefinition $table): void {
            $table->increments('id');
            $table->tinyInteger('type')->unsigned()->default(0)->nullable();
            $table->tinyInteger('view')->unsigned()->default(0)->nullable();
            $table->tinyInteger('layout')->unsigned()->default(0)->nullable();
            $table->integer('count')->unsigned()->default(0)->nullable();
            $table->integer('count_link')->unsigned()->default(0)->nullable();
            $table->text('name');
            $table->text('link');
            $table->integer('to')->unsigned()->default(0)->nullable();
            $table->string('color')->default('')->nullable();
            $table->integer('time')->unsigned()->default(0)->nullable();
            $table->integer('day')->unsigned()->default(0)->nullable();
            $table->tinyInteger('mesto')->unsigned()->default(0)->nullable();
            $table->tinyInteger('bold')->unsigned()->default(0)->nullable();
            $table->tinyInteger('italic')->unsigned()->default(0)->nullable();
            $table->tinyInteger('underline')->unsigned()->default(0)->nullable();
            $table->tinyInteger('show')->unsigned()->default(0)->nullable();
        });
    }

    private function createCmsBanIp(): void
    {
        if ($this->schema->hasTable('cms_ban_ip')) {
            return;
        }

        $this->schema->create('cms_ban_ip', static function (TableDefinition $table): void {
            $table->increments('id');
            $table->bigInteger('ip1')->default(0)->unique('ip1');
            $table->bigInteger('ip2')->default(0)->unique('ip2');
            $table->tinyInteger('ban_type')->default(0);
            $table->string('link')->default('');
            $table->string('who')->default('');
            $table->text('reason');
            $table->integer('date')->default(0);
        });
    }

    private function createCmsBanUsers(): void
    {
        if ($this->schema->hasTable('cms_ban_users')) {
            return;
        }

        $this->schema->create('cms_ban_users', static function (TableDefinition $table): void {
            $table->increments('id');
            $table->integer('user_id')->default(0)->index('user_id');
            $table->integer('ban_time')->default(0)->index('ban_time');
            $table->integer('ban_while')->default(0);
            $table->tinyInteger('ban_type')->default(1);
            $table->string('ban_who')->default('');
            $table->integer('ban_ref')->default(0);
            $table->text('ban_reason');
            $table->string('ban_raz')->default('');
        });
    }

    private function createCmsCounters(): void
    {
        if ($this->schema->hasTable('cms_counters')) {
            return;
        }

        $this->schema->create('cms_counters', static function (TableDefinition $table): void {
            $table->increments('id');
            $table->integer('sort')->default(1);
            $table->string('name')->default('');
            $table->text('link1');
            $table->text('link2');
            $table->tinyInteger('mode')->default(1);
            $table->boolean('switch')->default(0);
            $table->boolean('require_cookie_consent')->default(0);
        });
    }

    private function createCmsSessions(): void
    {
        if ($this->schema->hasTable('cms_sessions')) {
            return;
        }

        $this->schema->create('cms_sessions', static function (TableDefinition $table): void {
            $table->char('session_id', 32)->default('')->primary();
            $table->bigInteger('ip')->default(0);
            $table->bigInteger('ip_via_proxy')->default(0);
            $table->string('browser')->default('');
            $table->integer('lastdate')->unsigned()->default(0)->index('lastdate');
            $table->integer('sestime')->unsigned()->default(0);
            $table->integer('views')->unsigned()->default(0);
            $table->smallInteger('movings')->unsigned()->default(0);
            $table->text('place');
        });
    }

    private function createCmsUsersData(): void
    {
        if ($this->schema->hasTable('cms_users_data')) {
            return;
        }

        $this->schema->create('cms_users_data', static function (TableDefinition $table): void {
            $table->increments('id');
            $table->integer('user_id')->unsigned()->default(0)->index('user_id');
            $table->string('key')->default('')->index('key');
            $table->text('val');
        });
    }

    private function createCmsUsersGuestbook(): void
    {
        if ($this->schema->hasTable('cms_users_guestbook')) {
            return;
        }

        $this->schema->create('cms_users_guestbook', static function (TableDefinition $table): void {
            $table->increments('id');
            $table->integer('sub_id')->unsigned()->index('sub_id');
            $table->integer('time');
            $table->integer('user_id')->unsigned()->index('user_id');
            $table->text('text');
            $table->text('reply');
            $table->text('attributes');
        });
    }

    private function createCmsUsersIphistory(): void
    {
        if ($this->schema->hasTable('cms_users_iphistory')) {
            return;
        }

        $this->schema->create('cms_users_iphistory', static function (TableDefinition $table): void {
            $table->bigIncrements('id');
            $table->integer('user_id')->unsigned()->index('user_id');
            $table->bigInteger('ip')->default(0)->index('user_ip');
            $table->bigInteger('ip_via_proxy')->default(0);
            $table->integer('time')->unsigned();
        });
    }

    private function createKarmaUsers(): void
    {
        if ($this->schema->hasTable('karma_users')) {
            return;
        }

        $this->schema->create('karma_users', static function (TableDefinition $table): void {
            $table->increments('id');
            $table->integer('user_id')->unsigned()->default(0)->index('user_id');
            $table->string('name')->default('');
            $table->integer('karma_user')->unsigned()->default(0)->index('karma_user');
            $table->tinyInteger('points')->unsigned()->default(0);
            $table->tinyInteger('type')->unsigned()->default(0)->index('type');
            $table->integer('time')->unsigned()->default(0);
            $table->text('text');
        });
    }

    private function createUsers(): void
    {
        if ($this->schema->hasTable('users')) {
            return;
        }

        $this->schema->create('users', static function (TableDefinition $table): void {
            $table->increments('id');
            $table->string('name', 100)->default('');
            $table->string('name_lat', 100)->default('')->index('name_lat');
            $table->string('password')->default('');
            $table->string('imname')->default('');
            $table->string('sex', 5)->default('');
            $table->integer('komm')->unsigned()->default(0);
            $table->integer('postforum')->unsigned()->default(0);
            $table->integer('postguest')->unsigned()->default(0);
            $table->integer('yearofbirth')->unsigned()->default(0);
            $table->integer('datereg')->unsigned()->default(0);
            $table->integer('lastdate')->unsigned()->default(0)->index('lastdate');
            $table->string('mail')->default('');
            $table->integer('icq')->unsigned()->default(0);
            $table->string('skype')->default('');
            $table->string('jabber')->default('');
            $table->string('www')->default('');
            $table->text('about')->nullable();
            $table->string('live')->default('');
            $table->string('mibile')->default('');
            $table->string('status')->default('');
            $table->bigInteger('ip')->default(0);
            $table->bigInteger('ip_via_proxy')->default(0);
            $table->text('browser');
            $table->boolean('preg')->default(0);
            $table->string('regadm')->default('');
            $table->boolean('mailvis')->default(0);
            $table->integer('dayb')->default(0);
            $table->integer('monthb')->default(0);
            $table->integer('sestime')->unsigned()->default(0);
            $table->integer('total_on_site')->unsigned()->default(0);
            $table->integer('lastpost')->unsigned()->default(0);
            $table->integer('movings')->unsigned()->default(0);
            $table->text('place')->nullable();
            $table->text('set_user')->nullable();
            $table->text('set_forum')->nullable();
            $table->text('set_mail')->nullable();
            $table->integer('karma_plus')->default(0);
            $table->integer('karma_minus')->default(0);
            $table->integer('karma_time')->unsigned()->default(0);
            $table->boolean('karma_off')->default(0);
            $table->integer('comm_count')->unsigned()->default(0);
            $table->integer('comm_old')->unsigned()->default(0);
            $table->text('smileys')->nullable();
            $table->text('notification_settings')->nullable();
            $table->boolean('email_confirmed')->nullable();
            $table->string('confirmation_code')->nullable();
            $table->string('new_email')->nullable();
            $table->text('admin_notes')->nullable();
        });
    }

    private function createFiles(): void
    {
        if ($this->schema->hasTable('files')) {
            return;
        }

        $this->schema->create('files', static function (TableDefinition $table): void {
            $table->id();
            $table->string('storage')->index();
            $table->string('name');
            $table->string('path');
            $table->integer('size')->unsigned()->nullable();
            $table->string('md5', 32)->nullable();
            $table->string('sha1', 40)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
};
