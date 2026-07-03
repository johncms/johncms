<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

namespace Johncms\Modules\Guestbook\Install;

use Gettext\TranslatorFunctions;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use Johncms\System\i18n\Translator;

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
        // Load the module's own translation domain so demo strings are rendered in the language
        // selected by the user running the installer (falls back to the English source strings).
        $this->loadTranslations();

        $now = time();
        $ua = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36';

        $adminClubText = d__('guestbook', "Welcome to the Admin Club!\r\n"
            . "Only Moderators and Administrators have access here.\r\n"
            . 'Regular users are not allowed in.');

        $formattingText = d__('guestbook', 'The guestbook supports full text formatting in the visual editor:<br>' . "\n"
            . '<span style="font-weight: bold">bold</span><br>' . "\n"
            . '<span style="font-style:italic">italic</span><br>' . "\n"
            . '<span style="text-decoration:underline">underlined</span><br>' . "\n"
            . '<span style="color:red">red</span><br>' . "\n"
            . '<span style="color:green">green</span><br>' . "\n"
            . '<span style="color:blue">blue</span><br>' . "\n"
            . 'Inserting links: <a href="https://johncms.com">https://johncms.com</a>, images, tables, videos and much more');

        Capsule::table('guest')->insert([
            [
                'adm'     => 1,
                'time'    => $now,
                'user_id' => 1,
                'name'    => 'admin',
                'text'    => $adminClubText,
                'ip'      => 2130706433,
                'browser' => $ua,
                'admin'   => '',
                'otvet'   => '',
                'otime'   => 0,
            ],
            [
                'adm'     => 0,
                'time'    => $now,
                'user_id' => 1,
                'name'    => 'admin',
                'text'    => d__('guestbook', 'Welcome to the Guestbook!'),
                'ip'      => 2130706433,
                'browser' => $ua,
                'admin'   => 'admin',
                'otvet'   => d__('guestbook', 'A sample reply from the Administrator'),
                'otime'   => $now,
            ],
            [
                'adm'     => 0,
                'time'    => $now,
                'user_id' => 1,
                'name'    => 'admin',
                'text'    => $formattingText,
                'ip'      => 2130706433,
                'browser' => $ua,
                'admin'   => '',
                'otvet'   => '',
                'otime'   => 0,
            ],
        ]);
    }

    /**
     * Register the module's translation domain on the active (installer) translator so that
     * demo strings wrapped in d__('guestbook', ...) are translated into the installer's language.
     */
    private function loadTranslations(): void
    {
        $translator = TranslatorFunctions::getTranslator();
        if ($translator instanceof Translator) {
            // Keep the current default domain (e.g. 'install'); only add the guestbook catalog.
            $translator->addTranslationDomain('guestbook', MODULES_PATH . 'guestbook/locale', false);
        }
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
