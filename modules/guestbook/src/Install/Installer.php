<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

namespace Johncms\Modules\Guestbook\Install;

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
    }

    public function installDemoData(): void
    {
        $connection = Capsule::connection();

        $now = time();
        $ua = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36';

        $adminClubText = "Добро пожаловать в Админ Клуб!\r\n"
            . "Сюда имеют доступ ТОЛЬКО Модераторы и Администраторы.\r\n"
            . 'Простым пользователям доступ сюда закрыт.';

        $formattingText = 'Гостевая поддерживает полноценное форматирование текста в визуальном редакторе:<br>' . "\n"
            . '<span style="font-weight: bold">жирный</span><br>' . "\n"
            . '<span style="font-style:italic">курсив</span><br>' . "\n"
            . '<span style="text-decoration:underline">подчеркнутый</span><br>' . "\n"
            . '<span style="color:red">красный</span><br>' . "\n"
            . '<span style="color:green">зеленый</span><br>' . "\n"
            . '<span style="color:blue">синий</span><br>' . "\n"
            . 'Вставку ссылок: <a href="https://johncms.com">https://johncms.com</a>, картинок, таблиц, видео и многого другого';

        $connection->statement(
            'INSERT INTO `guest` (`adm`, `time`, `user_id`, `name`, `text`, `ip`, `browser`, `admin`, `otvet`, `otime`) VALUES '
            . "(1, $now, 1, 'admin', '$adminClubText', 2130706433, '$ua', '', '', 0),"
            . "(0, $now, 1, 'admin', 'Добро пожаловать в Гостевую!', 2130706433, '$ua', 'admin', 'Проверка ответа Администратора', $now),"
            . "(0, $now, 1, 'admin', '$formattingText', 2130706433, '$ua', '', '', 0);"
        );
    }

    private function createTables(): void
    {
        $schema = Capsule::schema();
        // Гостевая
        $schema->create(
            'guest',
            static function (Blueprint $table) {
                $table->increments('id');
                $table->boolean('adm')->default(0)->index('adm');
                $table->integer('time')->unsigned()->default(0)->index('time');
                $table->integer('user_id')->unsigned()->default(0);
                $table->string('name')->default('');
                $table->text('text');
                $table->bigInteger('ip')->default(0)->index('ip');
                $table->string('browser')->default('');
                $table->string('admin')->default('');
                $table->text('otvet');
                $table->integer('otime')->unsigned()->default(0);
                $table->string('edit_who')->default('');
                $table->integer('edit_time')->unsigned()->default(0);
                $table->tinyInteger('edit_count')->unsigned()->default(0);
                $table->longText('attached_files')->nullable();
            }
        );
    }
}
