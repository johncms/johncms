<?php

declare(strict_types=1);

namespace Johncms\Smilies;

use Johncms\Utils\Transliterator;

/**
 * The codes of the smilies and the pictures they stand for, taken from the folders and kept in a
 * cache file.
 *
 * Drawing the smilies reads the file only: it is built when it is missing or of an older format,
 * but the folders are not looked at on every request. Whether a picture was put in since is asked
 * by isStale(), which the admin panel does when its page of the smilies is opened.
 *
 * The pictures are kept as paths from the root of the site: the address of the site is added when
 * the smilies are drawn, so changing it does not leave the smilies pointing at the old one.
 */
final class SmiliesMap
{
    public const string CACHE_FILE = 'smilies-list.cache';

    private const int FORMAT = 2;
    private const string URL = 'assets/emoticons/';

    /** @var array{usr: array<string, string>, adm: array<string, string>}|null */
    private ?array $map = null;

    public function __construct(
        private readonly SmileyFolders $folders,
        private readonly string $cachePath = CACHE_PATH,
    ) {
    }

    /**
     * The codes everyone may use (`usr`) and those of the staff (`adm`), each to the path of the
     * picture from the root of the site.
     *
     * @return array{usr: array<string, string>, adm: array<string, string>}
     */
    public function get(): array
    {
        if ($this->map !== null) {
            return $this->map;
        }

        $cached = $this->read();
        if ($cached !== null) {
            return $this->map = $cached;
        }

        $map = $this->build();
        try {
            $this->write($map);
        } catch (SmiliesCacheWriteException) {
            // The smilies are drawn all the same; the next request tries to keep them again.
        }

        return $this->map = $map;
    }

    /**
     * Whether a picture was put in, taken out or renamed since the cache was built, or there is no
     * cache at all. Looks at every folder, so it is not for the requests of the site.
     */
    public function isStale(): bool
    {
        $modified = @filemtime($this->cachePath . self::CACHE_FILE);

        return $modified === false || $modified < $this->folders->lastModified();
    }

    /**
     * Builds the map from the folders and keeps it, whatever the cache holds.
     *
     * @return array{usr: array<string, string>, adm: array<string, string>}
     * @throws SmiliesCacheWriteException
     */
    public function rebuild(): array
    {
        $map = $this->build();
        $this->write($map);

        return $this->map = $map;
    }

    /**
     * @return array{usr: array<string, string>, adm: array<string, string>}|null
     */
    private function read(): ?array
    {
        $file = $this->cachePath . self::CACHE_FILE;
        if (! is_file($file)) {
            return null;
        }

        $content = @file_get_contents($file);
        $cached = $content === false ? false : unserialize($content, ['allowed_classes' => false]);
        if (! is_array($cached) || ($cached['format'] ?? null) !== self::FORMAT || ! isset($cached['usr'], $cached['adm'])) {
            return null;
        }

        return ['usr' => $cached['usr'], 'adm' => $cached['adm']];
    }

    /**
     * @param array{usr: array<string, string>, adm: array<string, string>} $map
     * @throws SmiliesCacheWriteException
     */
    private function write(array $map): void
    {
        $file = $this->cachePath . self::CACHE_FILE;
        // Written aside and moved in, so a request never reads a half written file.
        $temporary = $file . '.' . bin2hex(random_bytes(4));
        if (@file_put_contents($temporary, serialize(['format' => self::FORMAT] + $map)) === false || ! @rename($temporary, $file)) {
            @unlink($temporary);
            throw new SmiliesCacheWriteException(sprintf('Can not write the smilies cache file %s', $file));
        }
    }

    /**
     * @return array{usr: array<string, string>, adm: array<string, string>}
     */
    private function build(): array
    {
        $map = ['usr' => [], 'adm' => []];
        foreach ($this->folders->pictures() as $folder => $files) {
            foreach ($files as $file) {
                $name = pathinfo($file, PATHINFO_FILENAME);
                $path = self::URL . $folder . '/' . $file;

                if ($folder === SmileyFolders::SIMPLY) {
                    $map['usr'][':' . $name] = $path;
                    continue;
                }

                // A code is written in Latin letters or in their Cyrillic spelling.
                $group = $folder === SmileyFolders::ADMIN ? 'adm' : 'usr';
                $map[$group][':' . Transliterator::toCyrillic($name) . ':'] = $path;
                $map[$group][':' . $name . ':'] = $path;
            }
        }

        return $map;
    }
}
