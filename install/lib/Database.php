<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Install;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

class Database
{
    public static function createTables(bool $old_server = false): void
    {
        $schema = Capsule::schema();
        if ($old_server) {
            // For older versions of mysql
            $schema::defaultStringLength(191);
        }
        $schema->dropAllTables();

        // Реклама
        $schema->create(
            'cms_ads',
            static function (Blueprint $table) {
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
            }
        );

        // Баны
        $schema->create(
            'cms_ban_ip',
            static function (Blueprint $table) {
                $table->increments('id');
                $table->bigInteger('ip1')->default(0)->unique('ip1');
                $table->bigInteger('ip2')->default(0)->unique('ip2');
                $table->tinyInteger('ban_type')->default(0);
                $table->string('link')->default('');
                $table->string('who')->default('');
                $table->text('reason');
                $table->integer('date')->default(0);
            }
        );

        $schema->create(
            'cms_ban_users',
            static function (Blueprint $table) {
                $table->increments('id');
                $table->integer('user_id')->default(0)->index('user_id');
                $table->integer('ban_time')->default(0)->index('ban_time');
                $table->integer('ban_while')->default(0);
                $table->tinyInteger('ban_type')->default(1);
                $table->string('ban_who')->default('');
                $table->integer('ban_ref')->default(0);
                $table->text('ban_reason');
                $table->string('ban_raz')->default('');
            }
        );

        // Счетчики
        $schema->create(
            'cms_counters',
            static function (Blueprint $table) {
                $table->increments('id');
                $table->integer('sort')->default(1);
                $table->string('name')->default('');
                $table->text('link1');
                $table->text('link2');
                $table->tinyInteger('mode')->default(1);
                $table->boolean('switch')->default(0);
            }
        );

        // Гостевые сессии
        $schema->create(
            'cms_sessions',
            static function (Blueprint $table) {
                $table->char('session_id', 32)->default('')->primary();
                $table->bigInteger('ip')->default(0);
                $table->bigInteger('ip_via_proxy')->default(0);
                $table->string('browser')->default('');
                $table->integer('lastdate')->unsigned()->default(0)->index('lastdate');
                $table->integer('sestime')->unsigned()->default(0);
                $table->integer('views')->unsigned()->default(0);
                $table->smallInteger('movings')->unsigned()->default(0);
                $table->text('place');
            }
        );

        // Некоторые данные пользователя
        $schema->create(
            'cms_users_data',
            static function (Blueprint $table) {
                $table->increments('id');
                $table->integer('user_id')->unsigned()->default(0)->index('user_id');
                $table->string('key')->default('')->index('key');
                $table->text('val');
            }
        );

        // Пользовательские гостевые
        $schema->create(
            'cms_users_guestbook',
            static function (Blueprint $table) {
                $table->increments('id');
                $table->integer('sub_id')->unsigned()->index('sub_id');
                $table->integer('time');
                $table->integer('user_id')->unsigned()->index('user_id');
                $table->text('text');
                $table->text('reply');
                $table->text('attributes');
            }
        );

        // История IP
        $schema->create(
            'cms_users_iphistory',
            static function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->integer('user_id')->unsigned()->index('user_id');
                $table->bigInteger('ip')->default(0)->index('user_ip');
                $table->bigInteger('ip_via_proxy')->default(0);
                $table->integer('time')->unsigned();
            }
        );

        // Карма
        $schema->create(
            'karma_users',
            static function (Blueprint $table) {
                $table->increments('id');
                $table->integer('user_id')->unsigned()->default(0)->index('user_id');
                $table->string('name')->default('');
                $table->integer('karma_user')->unsigned()->default(0)->index('karma_user');
                $table->tinyInteger('points')->unsigned()->default(0);
                $table->tinyInteger('type')->unsigned()->default(0)->index('type');
                $table->integer('time')->unsigned()->default(0);
                $table->text('text');
            }
        );

        // Пользователи
        $schema->create(
            'users',
            static function (Blueprint $table) {
                $table->increments('id');
                $table->string('name', 100)->default('');
                $table->string('name_lat', 100)->default('')->index('name_lat');
                $table->string('password')->default('');
                $table->tinyInteger('rights')->unsigned()->default(0);
                $table->tinyInteger('failed_login')->unsigned()->default(0);
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
                $table->string('rest_code')->default('');
                $table->integer('rest_time')->unsigned()->default(0);
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
            }
        );

        // Email
        $schema->create(
            'email_messages',
            static function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->integer('priority')->nullable()->comment('Priority of sending the message');
                $table->string('locale', 8)->comment('The language used for displaying the message');
                $table->string('template')->comment('Template name');
                $table->text('fields')->nullable()->comment('Event fields');
                $table->timestamp('sent_at')->nullable()->comment('The time when the message was sent');
                $table->timestamps();
            }
        );

        $schema->create(
            'files',
            static function (Blueprint $table) {
                $table->id();
                $table->string('storage')->index();
                $table->string('name');
                $table->string('path');
                $table->integer('size')->unsigned()->nullable();
                $table->string('md5', 32)->nullable();
                $table->string('sha1', 40)->nullable();
                $table->timestamps();
                $table->softDeletes();
            }
        );
    }

    public static function installDemo(): void
    {
        $connection = Capsule::connection();

        $connection->statement(
            "INSERT INTO `forum_sections` (`id`, `parent`, `name`, `description`, `meta_description`, `meta_keywords`, `sort`, `access`, `section_type`) VALUES
(1, 0, 'Общение', 'Свободное общение на любую тему', '', NULL, 1, 0, 0),
(2, 1, 'О разном', '', '', NULL, 1, 0, 1),
(3, 1, 'Знакомства', '', '', NULL, 2, 0, 1),
(4, 1, 'Жизнь ресурса', '', '', NULL, 3, 0, 1),
(5, 1, 'Новости', '', '', NULL, 4, 0, 1),
(6, 1, 'Предложения и пожелания', '', '', NULL, 5, 0, 1),
(7, 1, 'Разное', '', '', NULL, 6, 0, 1);"
        );

        $connection->statement(
            "INSERT INTO `forum_topic` (`id`, `section_id`, `name`, `description`, `meta_description`, `meta_keywords`, `view_count`, `user_id`, `user_name`, `created_at`, `post_count`, `mod_post_count`, `last_post_date`, `last_post_author`, `last_post_author_name`, `last_message_id`, `mod_last_post_date`, `mod_last_post_author`, `mod_last_post_author_name`, `mod_last_message_id`, `closed`, `closed_by`, `deleted`, `deleted_by`, `curators`, `pinned`, `has_poll`) VALUES
(1, 3, 'Привет всем!', '', '', NULL, 1, 1, 'admin', '2019-10-16 20:18:00', 1, 1, 1571257080, 1, 'admin', 1, 1571257080, 1, 'admin', 1, NULL, NULL, NULL, NULL, '', NULL, NULL);"
        );
        $connection->statement(
            "INSERT INTO `forum_messages` (`id`, `topic_id`, `text`, `date`, `user_id`, `user_name`, `user_agent`, `ip`, `ip_via_proxy`, `pinned`, `editor_name`, `edit_time`, `edit_count`, `deleted`, `deleted_by`) VALUES
(1, 1, 'Мы рады приветствовать Вас на нашем сайте :)\r\nДавайте знакомиться!', 1571257080, 1, 'admin', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/77.0.3865.121 Safari/537.36 Vivaldi/2.8.1664.44', 0, 0, NULL, NULL, NULL, NULL, NULL, NULL);"
        );

        $connection->statement(
            "INSERT INTO `guest` (`adm`, `time`, `user_id`, `name`, `text`, `ip`, `browser`, `admin`, `otvet`, `otime`) VALUES
(1, 1217060516, 1, 'admin', 'Добро пожаловать в Админ Клуб!\r\nСюда имеют доступ ТОЛЬКО Модераторы и Администраторы.\r\nПростым пользователям доступ сюда закрыт.', 2130706433, 'Opera/9.51', '', '', 0),
(0, 1217060536, 1, 'admin', 'Добро пожаловать в Гостевую!', 2130706433, 'Opera/9.51', 'admin', 'Проверка ответа Администратора', 1217064021),
(0, 1217061125, 1, 'admin', 'Гостевая поддерживает полноценное форматирование текста в визуальном редакторе:<br>
<span style=\"font-weight: bold\">жирный</span><br>
<span style=\"font-style:italic\">курсив</span><br>
<span style=\"text-decoration:underline\">подчеркнутый</span><br>
<span style=\"color:red\">красный</span><br>
<span style=\"color:green\">зеленый</span><br>
<span style=\"color:blue\">синий</span><br>
Вставку ссылок: <a href=\"https://johncms.com\">https://johncms.com</a>, картинок, таблиц, видео и многого другого', 2130706433, 'Opera/9.51', '', '', 0);"
        );
    }
}
