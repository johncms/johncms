<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

namespace Johncms\Modules\Downloads\Install;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Str;

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
        $now = time();

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
                'rus_name' => 'Music',
                'desc'     => 'Demo royalty-free audio tracks',
                'source'   => 'music',
                'files'    => [
                    [
                        'file'     => 'grand_project-wonders-of-the-earth-550792.mp3',
                        'rus_name' => 'Grand Project — Wonders of the Earth',
                        'about'    => 'Atmospheric instrumental track for videos and podcasts.',
                        'user'     => null,
                        'rate'     => '7|1',
                        'field'    => 42,
                    ],
                    [
                        'file'     => 'puriosity_studio-button_mash_velocity-559021.mp3',
                        'rus_name' => 'Puriosity Studio — Button Mash Velocity',
                        'about'    => 'Energetic chiptune track, a great fit for game projects.',
                        'user'     => $uploaderA,
                        'rate'     => '4|0',
                        'field'    => 18,
                    ],
                    [
                        'file'     => 'sigmamusicart-no-copyright-music-537751.mp3',
                        'rus_name' => 'Sigma Music Art — No Copyright Music',
                        'about'    => 'Calm background music for streams and videos.',
                        'user'     => $uploaderB,
                        'rate'     => '3|1',
                        'field'    => 25,
                    ],
                ],
            ],
            [
                'id'       => 2,
                'name'     => 'Video',
                'rus_name' => 'Video',
                'desc'     => 'Demo video clips',
                'source'   => 'videos',
                'files'    => [
                    [
                        'file'     => '214669_tiny.mp4',
                        'rus_name' => 'Hornbill in the jungle',
                        'about'    => 'A short clip of a great hornbill against a tropical forest.',
                        'user'     => null,
                        'rate'     => '9|0',
                        'field'    => 63,
                    ],
                    [
                        'file'     => '278750_tiny.mp4',
                        'rus_name' => 'Mountain under storm clouds',
                        'about'    => 'A lone mountain on a green plain under heavy storm clouds.',
                        'user'     => $uploaderA,
                        'rate'     => '5|1',
                        'field'    => 31,
                    ],
                    [
                        'file'     => '345020_tiny.mp4',
                        'rus_name' => 'Snowstorm in the mountains',
                        'about'    => 'Snow-covered mountain slopes swept by a strong wind.',
                        'user'     => null,
                        'rate'     => '6|2',
                        'field'    => 27,
                    ],
                ],
            ],
            [
                'id'       => 3,
                'name'     => 'Images',
                'rus_name' => 'Images',
                'desc'     => 'Demo photos',
                'source'   => 'images',
                'files'    => [
                    [
                        'file'     => 'ahmetyuksek-lake-10359152_640.jpg',
                        'rus_name' => 'Mountain lake',
                        'about'    => 'A calm lake surrounded by mountains.',
                        'user'     => null,
                        'rate'     => '4|0',
                        'field'    => 15,
                    ],
                    [
                        'file'     => 'christels-grasshopper-10358078_640.jpg',
                        'rus_name' => 'Grasshopper',
                        'about'    => 'A macro photo of a grasshopper on a plant.',
                        'user'     => $uploaderA,
                        'rate'     => '3|0',
                        'field'    => 9,
                    ],
                    [
                        'file'     => 'erwinbosman-rooster-10359415_640.jpg',
                        'rus_name' => 'Rooster',
                        'about'    => 'A bright rooster in close-up.',
                        'user'     => null,
                        'rate'     => '2|1',
                        'field'    => 12,
                    ],
                    [
                        'file'     => 'neelam279-green-veined-white-10360180_640.jpg',
                        'rus_name' => 'Green-veined white butterfly',
                        'about'    => 'A green-veined white butterfly on a flower.',
                        'user'     => $uploaderB,
                        'rate'     => '5|0',
                        'field'    => 21,
                    ],
                    [
                        'file'     => 'terbe_rezso-bird-chick-10357944_640.jpg',
                        'rus_name' => 'Bird chick',
                        'about'    => 'A tiny bird chick in the grass.',
                        'user'     => null,
                        'rate'     => '6|1',
                        'field'    => 17,
                    ],
                    [
                        'file'     => 'terbe_rezso-squirrel-10357949_640.jpg',
                        'rus_name' => 'Squirrel',
                        'about'    => 'A red squirrel on a tree branch.',
                        'user'     => $uploaderA,
                        'rate'     => '8|0',
                        'field'    => 34,
                    ],
                ],
            ],
            [
                'id'       => 4,
                'name'     => 'Documents',
                'rus_name' => 'Documents',
                'desc'     => 'Demo documents and archives',
                'source'   => '',
                'files'    => [
                    [
                        'file'     => 'text-file.txt',
                        'rus_name' => 'Text document',
                        'about'    => 'An example of a plain text file.',
                        'user'     => null,
                        'rate'     => '1|0',
                        'field'    => 5,
                    ],
                    [
                        'file'     => 'text-file.zip',
                        'rus_name' => 'File archive',
                        'about'    => 'An example ZIP archive with nested files.',
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

    private function createTables(): void
    {
        $schema = Capsule::schema();

        // Закладки в загрузках
        $schema->create(
            'download__bookmark',
            static function (Blueprint $table) {
                $table->increments('id');
                $table->integer('user_id')->index('user_id');
                $table->integer('file_id')->index('file_id');
            }
        );

        // Категории в загрузках
        $schema->create(
            'download__category',
            static function (Blueprint $table) {
                $table->increments('id');
                $table->integer('refid')->unsigned()->default(0)->index('refid');
                $table->text('dir');
                $table->integer('sort')->default(0);
                $table->text('name');
                $table->string('slug')->nullable();
                $table->integer('total')->unsigned()->default(0)->index('total');
                $table->text('rus_name');
                $table->text('text');
                $table->integer('field')->unsigned()->default(0);
                $table->text('desc');
                $table->unique(['refid', 'slug'], 'download__category_refid_slug_unique');
            }
        );

        // Комментарии в загрузках
        $schema->create(
            'download__comments',
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

        // Файлы в загрузках
        $schema->create(
            'download__files',
            static function (Blueprint $table) {
                $table->increments('id');
                $table->integer('refid')->unsigned()->default(0)->index('refid');
                $table->text('dir');
                $table->integer('time')->unsigned()->default(0)->index('time');
                $table->text('name');
                $table->integer('type')->unsigned()->default(0)->index('type');
                $table->integer('user_id')->unsigned()->default(0)->index('user_id');
                $table->text('rus_name');
                $table->string('slug')->nullable();
                $table->text('text');
                $table->integer('field')->unsigned()->default(0);
                $table->string('rate')->default('0|0');
                $table->text('about');
                $table->text('desc');
                $table->integer('comm_count')->unsigned()->default(0)->index('comm_count');
                $table->unique(['refid', 'slug'], 'download__files_refid_slug_unique');
            }
        );

        // Доп файлы в загрузках
        $schema->create(
            'download__more',
            static function (Blueprint $table) {
                $table->increments('id');
                $table->integer('refid')->unsigned()->default(0)->index('refid');
                $table->integer('time')->unsigned()->default(0)->index('time');
                $table->text('name');
                $table->text('rus_name');
                $table->integer('size')->unsigned()->default(0);
            }
        );
    }
}
