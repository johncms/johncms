<?php

declare(strict_types=1);

namespace Johncms\Smilies;

/**
 * The folders the smilies are taken from, under public/assets/emoticons:
 *
 * - simply — codes without the closing colon, `:name`;
 * - admin — for the staff only, `:name:`;
 * - user/<category> — the categories of the catalogue, `:name:`.
 *
 * A picture is put in or taken out by hand, so the folders themselves are the list of the smilies.
 */
final readonly class SmileyFolders
{
    public const string SIMPLY = 'simply';
    public const string ADMIN = 'admin';
    public const string USER = 'user';

    private const array EXTENSIONS = ['gif', 'jpg', 'jpeg', 'png'];

    public function __construct(private string $root = ASSETS_PATH . 'emoticons')
    {
    }

    /**
     * The pictures of every folder, keyed by the folder relative to the root (`simply`, `admin`,
     * `user/animals`), each a list of file names.
     *
     * @return array<string, list<string>>
     */
    public function pictures(): array
    {
        $folders = [];
        foreach ($this->folders() as $folder) {
            $folders[$folder] = $this->files($this->root . DIRECTORY_SEPARATOR . $folder);
        }

        return $folders;
    }

    /**
     * The latest time a picture was put in or taken out: adding, removing or renaming a file
     * touches its folder, so the folders are enough to tell when the list has changed.
     */
    public function lastModified(): int
    {
        $times = [(int) @filemtime($this->root), (int) @filemtime($this->root . DIRECTORY_SEPARATOR . self::USER)];
        foreach ($this->folders() as $folder) {
            $times[] = (int) @filemtime($this->root . DIRECTORY_SEPARATOR . $folder);
        }

        return max($times);
    }

    /**
     * @return list<string>
     */
    private function folders(): array
    {
        $folders = [];
        foreach ([self::SIMPLY, self::ADMIN] as $folder) {
            if (is_dir($this->root . DIRECTORY_SEPARATOR . $folder)) {
                $folders[] = $folder;
            }
        }

        $categories = glob($this->root . DIRECTORY_SEPARATOR . self::USER . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR) ?: [];
        sort($categories);
        foreach ($categories as $path) {
            $folders[] = self::USER . '/' . basename($path);
        }

        return $folders;
    }

    /**
     * @return list<string>
     */
    private function files(string $path): array
    {
        $files = [];
        foreach (scandir($path) ?: [] as $name) {
            if (in_array(strtolower(pathinfo($name, PATHINFO_EXTENSION)), self::EXTENSIONS, true) && is_file($path . DIRECTORY_SEPARATOR . $name)) {
                $files[] = $name;
            }
        }

        return $files;
    }
}
