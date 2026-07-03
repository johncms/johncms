<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Modules\News\Install;

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

        $nowDt = date('Y-m-d H:i:s');
        $connection->statement(
            "INSERT INTO `news_sections` (`id`, `parent`, `name`, `code`, `text`, `keywords`, `description`, `created_at`, `updated_at`) VALUES
(1, 0, 'Новости сайта', 'novosti-sajta', NULL, NULL, NULL, '$nowDt', '$nowDt'),
(2, 0, 'Объявления', 'obyavleniya', NULL, NULL, NULL, '$nowDt', '$nowDt');"
        );
        $connection->statement(
            'INSERT INTO `news_articles` '
            . '(`id`, `section_id`, `active`, `active_from`, `active_to`, `name`, `page_title`, `code`, `keywords`, '
            . '`description`, `preview_text`, `text`, `view_count`, `tags`, `created_by`, `updated_by`, `attached_files`, '
            . '`created_at`, `updated_at`) VALUES '
            . "(1, 1, 1, NULL, NULL, 'Добро пожаловать на сайт!', NULL, 'dobro-pozhalovat-na-sajt', NULL, NULL, "
            . "'<p>Рады приветствовать вас на нашем сайте.</p>', "
            . "'<p>Рады приветствовать вас на нашем сайте. Здесь вы найдёте актуальные новости, полезные материалы и живое общение.</p>', "
            . "0, NULL, 1, 1, NULL, '$nowDt', '$nowDt'),"
            . "(2, 1, 1, NULL, NULL, 'Сайт запущен', NULL, 'sajt-zapushhen', NULL, NULL, "
            . "'<p>Наш сайт официально открыт для посетителей.</p>', "
            . "'<p>Наш сайт официально открыт для посетителей. Следите за обновлениями — впереди много интересного!</p>', "
            . "0, NULL, 1, 1, NULL, '$nowDt', '$nowDt'),"
            . "(3, 2, 1, NULL, NULL, 'Правила сайта', NULL, 'pravila-sajta', NULL, NULL, "
            . "'<p>Просим всех участников ознакомиться с правилами.</p>', "
            . "'<p>Просим всех участников ознакомиться с правилами сайта. Уважайте друг друга, не допускайте грубости и спама. "
            . "Администрация оставляет за собой право удалять материалы, нарушающие правила.</p>', "
            . "0, NULL, 1, 1, NULL, '$nowDt', '$nowDt'),"
            . "(4, 2, 1, NULL, NULL, 'Технические работы', NULL, 'tehnicheskie-raboty', NULL, NULL, "
            . "'<p>Плановые технические работы завершены.</p>', "
            . "'<p>Плановые технические работы успешно завершены. Все функции сайта работают в штатном режиме. Спасибо за терпение!</p>', "
            . "0, NULL, 1, 1, NULL, '$nowDt', '$nowDt');"
        );
        $connection->statement(
            "INSERT INTO `news_search_index` (`article_id`, `text`) VALUES
(1, 'Добро пожаловать на сайт! Рады приветствовать вас на нашем сайте. Здесь вы найдёте актуальные новости, полезные материалы и живое общение.'),
(2, 'Сайт запущен. Наш сайт официально открыт для посетителей. Следите за обновлениями — впереди много интересного!'),
(3, 'Правила сайта. Просим всех участников ознакомиться с правилами сайта. Уважайте друг друга, не допускайте грубости и спама.'),
(4, 'Технические работы. Плановые технические работы успешно завершены. Все функции сайта работают в штатном режиме.');"
        );
        $ua = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36';
        $ud = json_encode(['user_agent' => $ua, 'ip' => '127.0.0.1', 'ip_via_proxy' => ''], JSON_UNESCAPED_UNICODE);
        $connection->statement(
            "INSERT INTO `news_comments` (`article_id`, `user_id`, `text`, `user_data`, `created_at`) VALUES
(1, 1, '<p>Отличная новость! Рад видеть сайт в работе.</p>', '$ud', '$nowDt'),
(2, 1, '<p>Поздравляем с запуском! Впереди много всего интересного.</p>', '$ud', '$nowDt'),
(3, 1, '<p>Правила разумные, всё по делу.</p>', '$ud', '$nowDt'),
(4, 1, '<p>Спасибо за оперативное завершение работ!</p>', '$ud', '$nowDt');"
        );
        $connection->statement(
            "INSERT INTO `news_votes` (`article_id`, `user_id`, `vote`) VALUES
(1, 1, 1),(2, 1, 1),(3, 1, 1),(4, 1, 1);"
        );
    }

    private function createTables(): void
    {
        $schema = Capsule::schema();

        $schema->create(
            'news_sections',
            static function (Blueprint $table) {
                $table->increments('id');
                $table->integer('parent')->index()->nullable();
                $table->string('name');
                $table->string('code')->index()->nullable();
                $table->text('text')->nullable();
                $table->text('keywords')->nullable();
                $table->text('description')->nullable();
                $table->timestamps();
                $table->softDeletes();
            }
        );

        $schema->create(
            'news_articles',
            static function (Blueprint $table) {
                $table->increments('id');
                $table->integer('section_id')->unsigned()->nullable()->index();
                $table->boolean('active')->nullable();
                $table->dateTime('active_from')->nullable();
                $table->dateTime('active_to')->nullable();
                $table->string('name');
                $table->string('page_title')->nullable();
                $table->string('code')->index();
                $table->text('keywords')->nullable();
                $table->text('description')->nullable();
                $table->text('preview_text')->nullable();
                $table->longText('text');
                $table->integer('view_count')->nullable();
                $table->string('tags')->nullable()->index();
                $table->integer('created_by')->nullable();
                $table->integer('updated_by')->nullable();
                $table->longText('attached_files')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['section_id', 'code'], 'section_code');
            }
        );

        $schema->create(
            'news_votes',
            static function (Blueprint $table) {
                $table->increments('id');
                $table->integer('article_id')->unsigned();
                $table->integer('user_id')->unsigned();
                $table->integer('vote');

                $table->unique(['article_id', 'user_id'], 'article_user');

                $table->foreign('article_id')
                    ->references('id')
                    ->on('news_articles')
                    ->onUpdate('cascade')
                    ->onDelete('cascade');
            }
        );

        $schema->create(
            'news_search_index',
            static function (Blueprint $table) {
                $table->increments('id');
                $table->integer('article_id')->unsigned()->index();
                $table->longText('text');

                $table->foreign('article_id')
                    ->references('id')
                    ->on('news_articles')
                    ->onUpdate('cascade')
                    ->onDelete('cascade');
            }
        );

        $schema->create(
            'news_comments',
            static function (Blueprint $table) {
                $table->increments('id');
                $table->integer('article_id')->unsigned()->index();
                $table->integer('user_id')->unsigned();
                $table->longText('text');
                $table->text('user_data');
                $table->dateTime('created_at');
                $table->longText('attached_files')->nullable();
                $table->softDeletes();

                $table->foreign('article_id')
                    ->references('id')
                    ->on('news_articles')
                    ->onUpdate('cascade')
                    ->onDelete('cascade');
            }
        );
    }
}
