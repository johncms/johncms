<?php

declare(strict_types=1);

use Dom\HTMLDocument;
use Johncms\Database\Migrations\Migration;

return new class extends Migration {
    private const string OWNER_TYPE = 'guestbook';
    private const string DIRECTORY = 'guestbook/';
    private const int CHUNK = 500;

    public function useTransaction(): bool
    {
        return true;
    }

    /**
     * The pictures of the entries move into the registry of the editor uploads.
     *
     * An entry holds the files its list of attachments named and the files of the guestbook its
     * text or its reply shows, one file for each address. Only files of the guestbook directory
     * are taken: the list came from the browser unchecked and may name a file of somebody else.
     * A file an earlier entry already holds is registered for this one as a copy — a row of its
     * own over the same stored file — so deleting one entry never takes the picture of the other.
     * The uploader of every picture is the author of the entry.
     */
    public function up(): void
    {
        $now = date('Y-m-d H:i:s');
        $lastId = 0;

        do {
            $rows = $this->db->select(
                'SELECT id, user_id, text, otvet, attached_files FROM guest WHERE id > ? ORDER BY id LIMIT ' . self::CHUNK,
                [$lastId]
            );

            foreach ($rows as $row) {
                $lastId = (int) $row['id'];
                $files = $this->files(
                    $this->attachedIds((string) $row['attached_files']),
                    $this->shownPaths((string) $row['text'] . (string) $row['otvet'])
                );

                foreach ($files as $fileId) {
                    $this->register($this->ownFile($fileId, $now), $lastId, (int) $row['user_id'], $now);
                }
            }
        } while (count($rows) === self::CHUNK);
    }

    /**
     * @return list<int>
     */
    private function attachedIds(string $json): array
    {
        $ids = json_decode($json, true);
        if (! is_array($ids)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map(intval(...), array_filter($ids, is_numeric(...))), static fn(int $id): bool => $id > 0)));
    }

    /**
     * Paths of the files of the guestbook the text shows, relative to the disk.
     *
     * @return list<string>
     */
    private function shownPaths(string $html): array
    {
        if (! str_contains($html, '<img')) {
            return [];
        }

        $paths = [];
        $document = HTMLDocument::createFromString('<!DOCTYPE html><body>' . $html, LIBXML_NOERROR);
        foreach ($document->querySelectorAll('img[src]') as $image) {
            $path = rawurldecode((string) parse_url((string) $image->getAttribute('src'), PHP_URL_PATH));
            $start = strpos($path, '/' . self::DIRECTORY);
            if ($start !== false) {
                $paths[substr($path, $start + 1)] = true;
            }
        }

        return array_keys($paths);
    }

    /**
     * One file of the guestbook for each stored path: the attached one when there is, otherwise the
     * first file of that path.
     *
     * @param list<int> $attached
     * @param list<string> $paths
     * @return list<int>
     */
    private function files(array $attached, array $paths): array
    {
        $byPath = [];
        $attachedSet = array_flip($attached);
        $rows = [];
        if ($attached !== []) {
            $rows = $this->db->select(
                'SELECT id, path FROM files WHERE deleted_at IS NULL AND id IN (' . $this->placeholders($attached) . ')',
                $attached
            );
        }
        if ($paths !== []) {
            $rows = [
                ...$rows,
                ...$this->db->select(
                    'SELECT id, path FROM files WHERE deleted_at IS NULL AND path IN (' . $this->placeholders($paths) . ') ORDER BY id',
                    $paths
                ),
            ];
        }

        foreach ($rows as $row) {
            $path = (string) $row['path'];
            $id = (int) $row['id'];
            if (! str_starts_with($path, self::DIRECTORY)) {
                continue;
            }
            if (! isset($byPath[$path]) || (isset($attachedSet[$id]) && ! isset($attachedSet[$byPath[$path]]))) {
                $byPath[$path] = $id;
            }
        }

        return array_values($byPath);
    }

    /** The file itself when no entry holds it yet, otherwise a new row over the same stored file. */
    private function ownFile(int $fileId, string $now): int
    {
        if ($this->db->selectOne('SELECT id FROM editor_uploads WHERE file_id = ?', [$fileId]) === null) {
            return $fileId;
        }

        $this->db->execute(
            'INSERT INTO files (storage, name, path, size, md5, sha1, created_at, updated_at)'
            . ' SELECT storage, name, path, size, md5, sha1, ?, ? FROM files WHERE id = ?',
            [$now, $now, $fileId]
        );
        $copy = $this->db->selectOne(
            'SELECT MAX(c.id) AS id FROM files c JOIN files o ON o.storage = c.storage AND o.path = c.path WHERE o.id = ?',
            [$fileId]
        );

        return (int) ($copy['id'] ?? 0);
    }

    private function register(int $fileId, int $entryId, int $userId, string $now): void
    {
        $this->db->execute(
            'INSERT INTO editor_uploads (file_id, user_id, directory, owner_type, owner_id, created_at) VALUES (?, ?, ?, ?, ?, ?)',
            [$fileId, $userId, rtrim(self::DIRECTORY, '/'), self::OWNER_TYPE, $entryId, $now]
        );
    }

    /**
     * @param list<int|string> $values
     */
    private function placeholders(array $values): string
    {
        return implode(', ', array_fill(0, count($values), '?'));
    }
};
