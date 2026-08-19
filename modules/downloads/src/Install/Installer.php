<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

namespace Johncms\Modules\Downloads\Install;

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
        $now = time();

        // Load the module's own translation domain so demo strings are rendered in the language
        // selected by the user running the installer (falls back to the English source strings).
        $this->loadTranslations();

        // Physical storage: category directories live under upload/downloads/files, matching CreateCategoryController.
        // `dir` is stored as a relative path (resolved against the project root at runtime).
        $baseRelative = 'upload/downloads/files';
        $baseAbsolute = UPLOAD_PATH . 'downloads' . DS . 'files';
        if (! is_dir($baseAbsolute)) {
            mkdir($baseAbsolute, 0777, true);
        }

        $demoSource = MODULES_PATH . 'downloads' . DS . 'demo';

        // The first two non-admin users are seeded by the installer and act as demo uploaders.
        $demoUsers = Capsule::table('users')
            ->where('id', '>', 1)
            ->orderBy('id')
            ->limit(2)
            ->get()
            ->all();
        $uploaderA = $demoUsers[0] ?? null;
        $uploaderB = $demoUsers[1] ?? $demoUsers[0] ?? null;

        // Top-level categories. `name` must be alphanumeric because it is used as the on-disk directory name;
        // `rus_name` holds the human-readable, localizable title.
        $categories = [
            [
                'id'       => 1,
                'name'     => 'Music',
                'rus_name' => d__('downloads', 'Music'),
                'desc'     => d__('downloads', 'Demo royalty-free audio tracks'),
                'source'   => 'music',
                'files'    => [
                    [
                        'file'     => 'grand_project-wonders-of-the-earth-550792.mp3',
                        'rus_name' => 'Grand Project — Wonders of the Earth',
                        'about'    => d__('downloads', 'Atmospheric instrumental track for videos and podcasts.'),
                        'user'     => null,
                        'rate'     => '7|1',
                        'field'    => 42,
                    ],
                    [
                        'file'     => 'puriosity_studio-button_mash_velocity-559021.mp3',
                        'rus_name' => 'Puriosity Studio — Button Mash Velocity',
                        'about'    => d__('downloads', 'Energetic chiptune track, a great fit for game projects.'),
                        'user'     => $uploaderA,
                        'rate'     => '4|0',
                        'field'    => 18,
                    ],
                    [
                        'file'     => 'sigmamusicart-no-copyright-music-537751.mp3',
                        'rus_name' => 'Sigma Music Art — No Copyright Music',
                        'about'    => d__('downloads', 'Calm background music for streams and videos.'),
                        'user'     => $uploaderB,
                        'rate'     => '3|1',
                        'field'    => 25,
                    ],
                ],
            ],
            [
                'id'       => 2,
                'name'     => 'Video',
                'rus_name' => d__('downloads', 'Video'),
                'desc'     => d__('downloads', 'Demo video clips'),
                'source'   => 'videos',
                'files'    => [
                    [
                        'file'     => '214669_tiny.mp4',
                        'rus_name' => d__('downloads', 'Hornbill in the jungle'),
                        'about'    => d__('downloads', 'A short clip of a great hornbill against a tropical forest.'),
                        'user'     => null,
                        'rate'     => '9|0',
                        'field'    => 63,
                    ],
                    [
                        'file'     => '278750_tiny.mp4',
                        'rus_name' => d__('downloads', 'Mountain under storm clouds'),
                        'about'    => d__('downloads', 'A lone mountain on a green plain under heavy storm clouds.'),
                        'user'     => $uploaderA,
                        'rate'     => '5|1',
                        'field'    => 31,
                    ],
                    [
                        'file'     => '345020_tiny.mp4',
                        'rus_name' => d__('downloads', 'Snowstorm in the mountains'),
                        'about'    => d__('downloads', 'Snow-covered mountain slopes swept by a strong wind.'),
                        'user'     => null,
                        'rate'     => '6|2',
                        'field'    => 27,
                    ],
                ],
            ],
            [
                'id'       => 3,
                'name'     => 'Images',
                'rus_name' => d__('downloads', 'Images'),
                'desc'     => d__('downloads', 'Demo photos'),
                'source'   => 'images',
                'files'    => [
                    [
                        'file'     => 'ahmetyuksek-lake-10359152_640.jpg',
                        'rus_name' => d__('downloads', 'Mountain lake'),
                        'about'    => d__('downloads', 'A calm lake surrounded by mountains.'),
                        'user'     => null,
                        'rate'     => '4|0',
                        'field'    => 15,
                    ],
                    [
                        'file'     => 'christels-grasshopper-10358078_640.jpg',
                        'rus_name' => d__('downloads', 'Grasshopper'),
                        'about'    => d__('downloads', 'A macro photo of a grasshopper on a plant.'),
                        'user'     => $uploaderA,
                        'rate'     => '3|0',
                        'field'    => 9,
                    ],
                    [
                        'file'     => 'erwinbosman-rooster-10359415_640.jpg',
                        'rus_name' => d__('downloads', 'Rooster'),
                        'about'    => d__('downloads', 'A bright rooster in close-up.'),
                        'user'     => null,
                        'rate'     => '2|1',
                        'field'    => 12,
                    ],
                    [
                        'file'     => 'neelam279-green-veined-white-10360180_640.jpg',
                        'rus_name' => d__('downloads', 'Green-veined white butterfly'),
                        'about'    => d__('downloads', 'A green-veined white butterfly on a flower.'),
                        'user'     => $uploaderB,
                        'rate'     => '5|0',
                        'field'    => 21,
                    ],
                    [
                        'file'     => 'terbe_rezso-bird-chick-10357944_640.jpg',
                        'rus_name' => d__('downloads', 'Bird chick'),
                        'about'    => d__('downloads', 'A tiny bird chick in the grass.'),
                        'user'     => null,
                        'rate'     => '6|1',
                        'field'    => 17,
                    ],
                    [
                        'file'     => 'terbe_rezso-squirrel-10357949_640.jpg',
                        'rus_name' => d__('downloads', 'Squirrel'),
                        'about'    => d__('downloads', 'A red squirrel on a tree branch.'),
                        'user'     => $uploaderA,
                        'rate'     => '8|0',
                        'field'    => 34,
                    ],
                ],
            ],
            [
                'id'       => 4,
                'name'     => 'Documents',
                'rus_name' => d__('downloads', 'Documents'),
                'desc'     => d__('downloads', 'Demo documents and archives'),
                'source'   => '',
                'files'    => [
                    [
                        'file'     => 'text-file.txt',
                        'rus_name' => d__('downloads', 'Text document'),
                        'about'    => d__('downloads', 'An example of a plain text file.'),
                        'user'     => null,
                        'rate'     => '1|0',
                        'field'    => 5,
                    ],
                    [
                        'file'     => 'text-file.zip',
                        'rus_name' => d__('downloads', 'File archive'),
                        'about'    => d__('downloads', 'An example ZIP archive with nested files.'),
                        'user'     => null,
                        'rate'     => '2|0',
                        'field'    => 8,
                    ],
                ],
            ],
        ];

        $sort = $now;
        foreach ($categories as $category) {
            $categoryDirAbsolute = $baseAbsolute . DS . $category['name'];
            if (! is_dir($categoryDirAbsolute)) {
                mkdir($categoryDirAbsolute, 0777, true);
            }
            $categoryDirRelative = $baseRelative . '/' . $category['name'];

            Capsule::table('download__category')->insert([
                'id'       => $category['id'],
                'refid'    => 0,
                'dir'      => $categoryDirRelative,
                'sort'     => $sort--,
                'name'     => $category['name'],
                'slug'     => Str::slug($category['rus_name']),
                'total'    => count($category['files']),
                'rus_name' => $category['rus_name'],
                'text'     => '',
                'field'    => 0,
                'desc'     => $category['desc'],
            ]);

            $sourceDir = $demoSource . ($category['source'] !== '' ? DS . $category['source'] : '');
            foreach ($category['files'] as $fileData) {
                $sourcePath = $sourceDir . DS . $fileData['file'];
                $targetPath = $categoryDirAbsolute . DS . $fileData['file'];
                if (is_file($sourcePath) && ! is_file($targetPath)) {
                    copy($sourcePath, $targetPath);
                }

                Capsule::table('download__files')->insert([
                    'refid'      => $category['id'],
                    'dir'        => $categoryDirRelative,
                    'time'       => $now,
                    'name'       => $fileData['file'],
                    'type'       => 2,
                    'user_id'    => $fileData['user']->id ?? 1,
                    'rus_name'   => $fileData['rus_name'],
                    'slug'       => Str::slug($fileData['rus_name']),
                    'text'       => $fileData['rus_name'],
                    'field'      => $fileData['field'],
                    'rate'       => $fileData['rate'],
                    'about'      => $fileData['about'],
                    'desc'       => '',
                    'comm_count' => 0,
                ]);
            }
        }
    }

    /**
     * Register the module's translation domain on the active (installer) translator so that
     * demo strings wrapped in d__('downloads', ...) are translated into the installer's language.
     */
    private function loadTranslations(): void
    {
        $translator = TranslatorFunctions::getTranslator();
        if ($translator instanceof Translator) {
            // Keep the current default domain (e.g. 'install'); only add the downloads catalog.
            $translator->addTranslationDomain('downloads', MODULES_PATH . 'downloads/locale', false);
        }
    }
}
