<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

namespace Johncms\Modules\Forum\Install;

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

        $now = time();
        $userAgent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_3) AppleWebKit/537.36 (KHTML, like Gecko)'
            . ' Chrome/77.0.3865.121 Safari/537.36 Vivaldi/2.8.1664.44';

        // Sections. The first row is a top-level directory (section_type = 0); the rest are its
        // child sections (section_type = 1). Slugs are derived from the localized name.
        $sections = [
            ['id' => 1, 'parent' => 0, 'name' => d__('forum', 'Communication'), 'description' => d__('forum', 'Free discussion on any topic'), 'sort' => 1, 'section_type' => 0],
            ['id' => 2, 'parent' => 1, 'name' => d__('forum', 'Off-topic'), 'description' => '', 'sort' => 1, 'section_type' => 1],
            ['id' => 3, 'parent' => 1, 'name' => d__('forum', 'Dating'), 'description' => '', 'sort' => 2, 'section_type' => 1],
            ['id' => 4, 'parent' => 1, 'name' => d__('forum', 'Site life'), 'description' => '', 'sort' => 3, 'section_type' => 1],
            ['id' => 5, 'parent' => 1, 'name' => d__('forum', 'News'), 'description' => '', 'sort' => 4, 'section_type' => 1],
            ['id' => 6, 'parent' => 1, 'name' => d__('forum', 'Suggestions and wishes'), 'description' => '', 'sort' => 5, 'section_type' => 1],
            ['id' => 7, 'parent' => 1, 'name' => d__('forum', 'Miscellaneous'), 'description' => '', 'sort' => 6, 'section_type' => 1],
        ];

        $sectionRows = [];
        foreach ($sections as $section) {
            $sectionRows[] = [
                'id'               => $section['id'],
                'parent'           => $section['parent'],
                'name'             => $section['name'],
                'slug'             => Str::slug($section['name']),
                'description'      => $section['description'],
                'meta_description' => '',
                'meta_keywords'    => null,
                'sort'             => $section['sort'],
                'access'           => 0,
                'section_type'     => $section['section_type'],
            ];
        }
        Capsule::table('forum_sections')->insert($sectionRows);

        // Demo topic in the "Dating" section, authored by the admin.
        $topicName = d__('forum', 'Hello everyone!');
        Capsule::table('forum_topic')->insert([
            'id'                        => 1,
            'section_id'                => 3,
            'name'                      => $topicName,
            'slug'                      => Str::slug($topicName),
            'description'               => '',
            'meta_description'          => '',
            'meta_keywords'             => null,
            'view_count'                => 1,
            'user_id'                   => 1,
            'user_name'                 => 'admin',
            'created_at'                => date('Y-m-d H:i:s', $now),
            'post_count'                => 1,
            'mod_post_count'            => 1,
            'last_post_date'            => $now,
            'last_post_author'          => 1,
            'last_post_author_name'     => 'admin',
            'last_message_id'           => 1,
            'mod_last_post_date'        => $now,
            'mod_last_post_author'      => 1,
            'mod_last_post_author_name' => 'admin',
            'mod_last_message_id'       => 1,
            'closed'                    => null,
            'closed_by'                 => null,
            'deleted'                   => null,
            'deleted_by'                => null,
            'curators'                  => '',
            'pinned'                    => null,
            'has_poll'                  => null,
        ]);

        // First message of the demo topic.
        Capsule::table('forum_messages')->insert([
            'id'           => 1,
            'topic_id'     => 1,
            'text'         => d__('forum', "<p>We are glad to welcome you to our website :)</p><p>Let's get acquainted!</p>"),
            'date'         => $now,
            'user_id'      => 1,
            'user_name'    => 'admin',
            'user_agent'   => $userAgent,
            'ip'           => 2130706433,
            'ip_via_proxy' => 0,
            'pinned'       => null,
            'editor_name'  => null,
            'edit_time'    => null,
            'edit_count'   => null,
            'deleted'      => null,
            'deleted_by'   => null,
        ]);
    }

    /**
     * Register the module's translation domain on the active (installer) translator so that
     * demo strings wrapped in d__('forum', ...) are translated into the installer's language.
     */
    private function loadTranslations(): void
    {
        $translator = TranslatorFunctions::getTranslator();
        if ($translator instanceof Translator) {
            // Keep the current default domain (e.g. 'install'); only add the forum catalog.
            $translator->addTranslationDomain('forum', MODULES_PATH . 'johncms/forum/locale', false);
        }
    }
}
