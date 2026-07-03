<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Modules\Album\Install;

use Gettext\TranslatorFunctions;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use Intervention\Image\ImageManager;
use Johncms\Modules\Album\Domain\Enums\AlbumAccess;
use Johncms\System\i18n\Translator;
use Throwable;

class Installer extends \Johncms\Modules\Installer
{
    private const THUMB_WIDTH = 400;
    private const THUMB_HEIGHT = 300;

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

        // Load the module's own translation domain so demo strings are rendered in the language
        // selected by the user running the installer (falls back to the English source strings).
        $this->loadTranslations();

        // Demo photos are shipped with the module and copied into the per-user album directory on install.
        $demoSource = MODULES_PATH . 'album' . DS . 'demo' . DS . 'images';

        // The first two non-admin users are seeded by the installer and act as demo album owners and commenters.
        $demoUsers = Capsule::table('users')
            ->where('id', '>', 1)
            ->orderBy('id')
            ->limit(2)
            ->get()
            ->all();
        $ownerA = $demoUsers[0] ?? null;
        $ownerB = $demoUsers[1] ?? $demoUsers[0] ?? null;

        // Albums for the admin and the demo users, covering every access level:
        // public (visible to everyone), password-protected and private.
        // Each photo's `access` mirrors its album, matching how the app cascades album access to photos.
        $albums = [
            [
                'id'          => 1,
                'user'        => null,
                'name'        => d__('album', 'Nature'),
                'description' => d__('album', 'Landscapes and nature views'),
                'access'      => AlbumAccess::Public,
                'password'    => null,
                'photos'      => [
                    ['id' => 1, 'file' => 'ahmetyuksek-lake-10359152_640.jpg', 'description' => d__('album', 'A mountain lake surrounded by peaks'), 'vote_plus' => 8, 'vote_minus' => 1, 'views' => 42],
                    ['id' => 2, 'file' => 'terbe_rezso-bird-chick-10357944_640.jpg', 'description' => d__('album', 'A tiny bird chick in the grass'), 'vote_plus' => 5, 'vote_minus' => 0, 'views' => 27],
                ],
            ],
            [
                'id'          => 2,
                'user'        => null,
                'name'        => d__('album', 'Animals'),
                'description' => d__('album', 'Photos of animals and insects'),
                'access'      => AlbumAccess::Public,
                'password'    => null,
                'photos'      => [
                    ['id' => 3, 'file' => 'erwinbosman-rooster-10359415_640.jpg', 'description' => d__('album', 'A bright rooster in close-up'), 'vote_plus' => 3, 'vote_minus' => 1, 'views' => 19],
                    ['id' => 4, 'file' => 'terbe_rezso-squirrel-10357949_640.jpg', 'description' => d__('album', 'A red squirrel on a tree branch'), 'vote_plus' => 9, 'vote_minus' => 0, 'views' => 56],
                ],
            ],
            [
                'id'          => 3,
                'user'        => $ownerA,
                'name'        => d__('album', 'My photos'),
                'description' => d__('album', 'A personal collection of favorite shots'),
                'access'      => AlbumAccess::Public,
                'password'    => null,
                'photos'      => [
                    ['id' => 5, 'file' => 'neelam279-green-veined-white-10360180_640.jpg', 'description' => d__('album', 'A butterfly on a flower'), 'vote_plus' => 6, 'vote_minus' => 0, 'views' => 31],
                    ['id' => 6, 'file' => 'christels-grasshopper-10358078_640.jpg', 'description' => d__('album', 'A grasshopper on a plant'), 'vote_plus' => 2, 'vote_minus' => 0, 'views' => 14],
                ],
            ],
            [
                'id'          => 4,
                'user'        => $ownerB,
                'name'        => d__('album', 'Private album'),
                'description' => d__('album', 'A private album visible to its owner only'),
                'access'      => AlbumAccess::Private,
                'password'    => null,
                'photos'      => [
                    ['id' => 7, 'file' => 'ahmetyuksek-lake-10359152_640.jpg', 'description' => d__('album', 'A shot from a private album'), 'vote_plus' => 0, 'vote_minus' => 0, 'views' => 3],
                ],
            ],
            [
                'id'          => 5,
                'user'        => null,
                'name'        => d__('album', 'Password-protected album'),
                'description' => d__('album', 'An album protected by a password (password: demo)'),
                'access'      => AlbumAccess::Password,
                'password'    => 'demo',
                'photos'      => [
                    ['id' => 8, 'file' => 'terbe_rezso-squirrel-10357949_640.jpg', 'description' => d__('album', 'A password-protected photo'), 'vote_plus' => 1, 'vote_minus' => 0, 'views' => 7],
                ],
            ],
        ];

        $sort = count($albums);
        $fileSeq = 0;
        foreach ($albums as $album) {
            $ownerId = $album['user']->id ?? 1;

            Capsule::table('cms_album_cat')->insert([
                'id'          => $album['id'],
                'user_id'     => $ownerId,
                'sort'        => $sort--,
                'name'        => $album['name'],
                'description' => $album['description'],
                'password'    => $album['password'],
                'access'      => $album['access']->value,
            ]);

            $albumDir = UPLOAD_PATH . 'users' . DS . 'album' . DS . $ownerId . DS;
            if (! is_dir($albumDir)) {
                mkdir($albumDir, 0777, true);
            }

            foreach ($album['photos'] as $photo) {
                $fileTime = $now + $fileSeq++;
                $imgName = 'img_' . $fileTime . '.jpg';
                $tmbName = 'tmb_' . $fileTime . '.jpg';

                $sourcePath = $demoSource . DS . $photo['file'];
                if (is_file($sourcePath)) {
                    $this->copyDemoPhoto($sourcePath, $albumDir . $imgName, $albumDir . $tmbName);
                }

                Capsule::table('cms_album_files')->insert([
                    'id'              => $photo['id'],
                    'user_id'         => $ownerId,
                    'album_id'        => $album['id'],
                    'description'     => $photo['description'],
                    'img_name'        => $imgName,
                    'tmb_name'        => $tmbName,
                    'time'            => $fileTime,
                    'comments'        => 1,
                    'comm_count'      => 0,
                    'access'          => $album['access']->value,
                    'vote_plus'       => $photo['vote_plus'],
                    'vote_minus'      => $photo['vote_minus'],
                    'views'           => $photo['views'],
                    'downloads'       => 0,
                    'unread_comments' => 0,
                ]);
            }
        }

        $this->seedCommentsAndVotes($now, $ownerA, $ownerB);
    }

    /**
     * Register the module's translation domain on the active (installer) translator so that
     * demo strings wrapped in d__('album', ...) are translated into the installer's language.
     */
    private function loadTranslations(): void
    {
        $translator = TranslatorFunctions::getTranslator();
        if ($translator instanceof Translator) {
            // Keep the current default domain (e.g. 'install'); only add the album catalog.
            $translator->addTranslationDomain('album', MODULES_PATH . 'album/locale', false);
        }
    }

    /**
     * Copy a demo photo into the album directory and build a blurred thumbnail,
     * mirroring the runtime upload flow. Falls back to the original image if
     * thumbnail generation is unavailable.
     */
    private function copyDemoPhoto(string $sourcePath, string $originalTarget, string $thumbTarget): void
    {
        if (! is_file($originalTarget)) {
            copy($sourcePath, $originalTarget);
        }

        if (is_file($thumbTarget)) {
            return;
        }

        try {
            $imageManager = di(ImageManager::class);
            $resized = $imageManager->make($sourcePath)
                ->resize(
                    self::THUMB_WIDTH,
                    self::THUMB_HEIGHT,
                    static function ($constraint): void {
                        /** @var \Intervention\Image\Constraint $constraint */
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    }
                );

            $imageManager->make($sourcePath)
                ->fit(self::THUMB_WIDTH, self::THUMB_HEIGHT)
                ->blur(20)
                ->insert($resized, 'center')
                ->save($thumbTarget, 100, 'jpg');
        } catch (Throwable) {
            // Thumbnail generation is best-effort; fall back to the full-size image.
            copy($sourcePath, $thumbTarget);
        }
    }

    /**
     * Seed demo comments and votes from the demo users on the public photos,
     * keeping each photo's cached comment counter in sync.
     */
    private function seedCommentsAndVotes(int $now, ?object $ownerA, ?object $ownerB): void
    {
        $ua = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36';

        // Comments from demo users on a few public photos.
        $comments = [
            ['sub_id' => 1, 'user' => $ownerA, 'text' => d__('album', 'A beautiful lake, such an atmospheric shot!')],
            ['sub_id' => 1, 'user' => $ownerB, 'text' => d__('album', 'I would love to visit this place.')],
            ['sub_id' => 4, 'user' => $ownerB, 'text' => d__('album', 'Great shot of the squirrel!')],
            ['sub_id' => 5, 'user' => $ownerB, 'text' => d__('album', 'A lovely butterfly.')],
        ];

        $commentCounts = [];
        foreach ($comments as $comment) {
            if ($comment['user'] === null) {
                continue;
            }
            Capsule::table('cms_album_comments')->insert([
                'sub_id'     => $comment['sub_id'],
                'time'       => $now,
                'user_id'    => $comment['user']->id,
                'text'       => $comment['text'],
                'reply'      => '',
                'attributes' => serialize([
                    'author_name'         => $comment['user']->name,
                    'author_ip'           => 2130706433,
                    'author_ip_via_proxy' => 0,
                    'author_browser'      => $ua,
                ]),
            ]);
            $commentCounts[$comment['sub_id']] = ($commentCounts[$comment['sub_id']] ?? 0) + 1;
        }

        foreach ($commentCounts as $photoId => $count) {
            Capsule::table('cms_album_files')->where('id', $photoId)->update(['comm_count' => $count]);
        }

        // Votes from demo users on a few photos (one vote per user and photo).
        $votes = [
            ['file_id' => 1, 'user' => $ownerA, 'vote' => 1],
            ['file_id' => 1, 'user' => $ownerB, 'vote' => 1],
            ['file_id' => 4, 'user' => $ownerA, 'vote' => 1],
            ['file_id' => 2, 'user' => $ownerB, 'vote' => 1],
        ];
        foreach ($votes as $vote) {
            if ($vote['user'] === null) {
                continue;
            }
            Capsule::table('cms_album_votes')->insert([
                'user_id' => $vote['user']->id,
                'file_id' => $vote['file_id'],
                'vote'    => $vote['vote'],
            ]);
        }
    }

    private function createTables(): void
    {
        $schema = Capsule::schema();
        $schema->create(
            'cms_album_cat',
            static function (Blueprint $table) {
                $table->increments('id');
                $table->integer('user_id')->unsigned()->index('user_id');
                $table->integer('sort')->unsigned()->default(0);
                $table->string('name');
                $table->text('description');
                $table->string('password')->nullable();
                $table->integer('access')->nullable()->index('access');
            }
        );

        $schema->create(
            'cms_album_comments',
            static function (Blueprint $table) {
                $table->increments('id');
                $table->integer('sub_id')->unsigned()->default(0)->index('sub_id');
                $table->integer('time')->unsigned()->default(0);
                $table->integer('user_id')->unsigned()->default(0)->index('user_id');
                $table->text('text');
                $table->text('reply');
                $table->text('attributes');
            }
        );

        $schema->create(
            'cms_album_downloads',
            static function (Blueprint $table) {
                $table->integer('user_id')->unsigned()->default(0);
                $table->integer('file_id')->unsigned()->default(0);
                $table->integer('time')->unsigned()->default(0);
                $table->primary(['user_id', 'file_id'], 'user_file');
            }
        );

        $schema->create(
            'cms_album_files',
            static function (Blueprint $table) {
                $table->increments('id');
                $table->integer('user_id')->unsigned()->index('user_id');
                $table->integer('album_id')->unsigned()->index('album_id');
                $table->text('description');
                $table->string('img_name')->default('');
                $table->string('tmb_name')->default('');
                $table->integer('time')->unsigned()->default(0);
                $table->boolean('comments')->default(1);
                $table->integer('comm_count')->unsigned()->default(0);
                $table->tinyInteger('access')->unsigned()->default(0)->index('access');
                $table->integer('vote_plus')->default(0);
                $table->integer('vote_minus')->default(0);
                $table->integer('views')->unsigned()->default(0);
                $table->integer('downloads')->unsigned()->default(0);
                $table->boolean('unread_comments')->default(0);
            }
        );

        $schema->create(
            'cms_album_views',
            static function (Blueprint $table) {
                $table->integer('user_id')->unsigned()->default(0);
                $table->integer('file_id')->unsigned()->default(0);
                $table->integer('time')->unsigned()->default(0);
                $table->primary(['user_id', 'file_id'], 'user_file');
            }
        );

        $schema->create(
            'cms_album_votes',
            static function (Blueprint $table) {
                $table->increments('id');
                $table->integer('user_id')->unsigned()->default(0)->index('user_id');
                $table->integer('file_id')->unsigned()->default(0)->index('file_id');
                $table->tinyInteger('vote');
            }
        );
    }
}
