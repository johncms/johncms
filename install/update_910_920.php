<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

const DEBUG = true;
const _IN_JOHNCMS = true;

require '../system/bootstrap.php';

// Меняем тип таблицы пользователей
Capsule::connection()->statement('ALTER TABLE users ENGINE = InnoDB');

Capsule::Schema()->table(
    'users',
    static function (Blueprint $table) {
        $table->text('notification_settings')->nullable()->comment('Notification settings');
    }
);

// Создаем таблицу уведомлений
Capsule::Schema()->create(
    'notifications',
    static function (Blueprint $table) {
        $table->increments('id');
        $table->string('module')->comment('Module name');
        $table->string('event_type')->comment('Event type');
        $table->integer('user_id')->unsigned()->index()->comment('User identifier');
        $table->integer('sender_id')->unsigned()->nullable()->comment('Sender identifier');
        $table->integer('entity_id')->unsigned()->nullable()->comment('Entity identifier');
        $table->text('fields')->nullable()->comment('Event fields');
        $table->timestamp('read_at')->nullable()->comment('Read date');
        $table->timestamps();

        $table->foreign('user_id')
            ->references('id')
            ->on('users')
            ->onUpdate('cascade')
            ->onDelete('cascade');
        $table->index(['user_id', 'module', 'event_type', 'entity_id']);
    }
);


echo 'Update complete!';
