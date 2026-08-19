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

use Gettext\TranslatorFunctions;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Support\Str;
use Johncms\System\i18n\Translator;

class Installer extends \Johncms\Modules\Installer
{
    public function uninstall(): void
    {
    }

    public function installDemoData(): void
    {
        // Load the module's own translation domain so demo strings are rendered in the language
        // selected by the user running the installer (falls back to the English source strings).
        $this->loadTranslations();

        $nowDt = date('Y-m-d H:i:s');

        // Sections. `code` is derived from the localized name.
        $sections = [
            ['id' => 1, 'name' => d__('news', 'Site news')],
            ['id' => 2, 'name' => d__('news', 'Announcements')],
        ];
        $sectionRows = [];
        foreach ($sections as $section) {
            $sectionRows[] = [
                'id'          => $section['id'],
                'parent'      => 0,
                'name'        => $section['name'],
                'code'        => Str::slug($section['name']),
                'text'        => null,
                'keywords'    => null,
                'description' => null,
                'created_at'  => $nowDt,
                'updated_at'  => $nowDt,
            ];
        }
        Capsule::table('news_sections')->insert($sectionRows);

        // Articles. `code` is derived from the localized name; the search index is built from the
        // localized name and body text so it stays consistent with the installer language.
        $articles = [
            [
                'id'           => 1,
                'section_id'   => 1,
                'name'         => d__('news', 'Welcome to the site!'),
                'preview_text' => d__('news', '<p>We are glad to welcome you to our site.</p>'),
                'text'         => d__('news', '<p>We are glad to welcome you to our site. Here you will find the latest news, useful materials and lively discussion.</p>'),
            ],
            [
                'id'           => 2,
                'section_id'   => 1,
                'name'         => d__('news', 'The site is launched'),
                'preview_text' => d__('news', '<p>Our site is officially open to visitors.</p>'),
                'text'         => d__('news', '<p>Our site is officially open to visitors. Stay tuned for updates — a lot of interesting things are coming!</p>'),
            ],
            [
                'id'           => 3,
                'section_id'   => 2,
                'name'         => d__('news', 'Site rules'),
                'preview_text' => d__('news', '<p>We ask all members to read the rules.</p>'),
                'text'         => d__('news', '<p>We ask all members to read the site rules. Respect each other, avoid rudeness and spam. The administration reserves the right to remove materials that violate the rules.</p>'),
            ],
            [
                'id'           => 4,
                'section_id'   => 2,
                'name'         => d__('news', 'Maintenance'),
                'preview_text' => d__('news', '<p>The scheduled maintenance is complete.</p>'),
                'text'         => d__('news', '<p>The scheduled maintenance has been completed successfully. All site features are working normally. Thank you for your patience!</p>'),
            ],
        ];

        $articleRows = [];
        $searchRows = [];
        foreach ($articles as $article) {
            $articleRows[] = [
                'id'             => $article['id'],
                'section_id'     => $article['section_id'],
                'active'         => 1,
                'active_from'    => null,
                'active_to'      => null,
                'name'           => $article['name'],
                'page_title'     => null,
                'code'           => Str::slug($article['name']),
                'keywords'       => null,
                'description'    => null,
                'preview_text'   => $article['preview_text'],
                'text'           => $article['text'],
                'view_count'     => 0,
                'tags'           => null,
                'created_by'     => 1,
                'updated_by'     => 1,
                'attached_files' => null,
                'created_at'     => $nowDt,
                'updated_at'     => $nowDt,
            ];
            $searchRows[] = [
                'article_id' => $article['id'],
                'text'       => $article['name'] . ' ' . strip_tags($article['text']),
            ];
        }
        Capsule::table('news_articles')->insert($articleRows);
        Capsule::table('news_search_index')->insert($searchRows);

        $ua = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36';
        $userData = json_encode(['user_agent' => $ua, 'ip' => '127.0.0.1', 'ip_via_proxy' => ''], JSON_UNESCAPED_UNICODE);

        $comments = [
            ['article_id' => 1, 'text' => d__('news', '<p>Great news! Glad to see the site up and running.</p>')],
            ['article_id' => 2, 'text' => d__('news', '<p>Congratulations on the launch! A lot of interesting things are ahead.</p>')],
            ['article_id' => 3, 'text' => d__('news', '<p>The rules are reasonable, all to the point.</p>')],
            ['article_id' => 4, 'text' => d__('news', '<p>Thanks for completing the work so quickly!</p>')],
        ];
        $commentRows = [];
        foreach ($comments as $comment) {
            $commentRows[] = [
                'article_id' => $comment['article_id'],
                'user_id'    => 1,
                'text'       => $comment['text'],
                'user_data'  => $userData,
                'created_at' => $nowDt,
            ];
        }
        Capsule::table('news_comments')->insert($commentRows);

        $voteRows = [];
        foreach ($articles as $article) {
            $voteRows[] = [
                'article_id' => $article['id'],
                'user_id'    => 1,
                'vote'       => 1,
            ];
        }
        Capsule::table('news_votes')->insert($voteRows);
    }

    /**
     * Register the module's translation domain on the active (installer) translator so that
     * demo strings wrapped in d__('news', ...) are translated into the installer's language.
     */
    private function loadTranslations(): void
    {
        $translator = TranslatorFunctions::getTranslator();
        if ($translator instanceof Translator) {
            // Keep the current default domain (e.g. 'install'); only add the news catalog.
            $translator->addTranslationDomain('news', MODULES_PATH . 'news/locale', false);
        }
    }
}
