<?php

declare(strict_types=1);

use Dom\HTMLDocument;
use Johncms\Database\Migrations\Migration;

return new class extends Migration {
    private const string OWNER_TYPE = 'forum_message';
    private const string DIRECTORY = 'forum_files/';
    private const int CHUNK = 500;

    public function useTransaction(): bool
    {
        return true;
    }

    /**
     * The pictures of the text of the posts move into the registry of the editor uploads.
     *
     * A post holds the files its links named and the files of the forum its text shows, one file
     * for each address. Only files of the forum directory are taken: the links came from a list
     * the browser sent, checked for the directory only. A file an earlier post already holds is
     * registered for this one as a copy — a row of its own over the same stored file — so deleting
     * one post never takes the picture of the other. The uploader of every picture is the author
     * of the post.
     */
    public function up(): void
    {
        $now = date('Y-m-d H:i:s');
        $lastId = 0;

        do {
            $rows = $this->db->select(
                'SELECT id, user_id, text FROM forum_messages WHERE id > ?'
                . ' AND (text LIKE ? OR id IN (SELECT message_id FROM forum_message_files))'
                . ' ORDER BY id LIMIT ' . self::CHUNK,
                [$lastId, '%' . self::DIRECTORY . '%']
            );
            $linked = $this->linkedFiles(array_map(static fn(array $row): int => (int) $row['id'], $rows));

            foreach ($rows as $row) {
                $lastId = (int) $row['id'];
                $files = $this->files($linked[$lastId] ?? [], $this->shownPaths((string) $row['text']));

                foreach ($files as $fileId) {
                    $this->register($this->ownFile($fileId, $now), $lastId, (int) $row['user_id'], $now);
                }
            }
        } while (count($rows) === self::CHUNK);
    }

    /**
     * @param list<int> $messageIds
     * @return array<int, list<int>>
     */
    private function linkedFiles(array $messageIds): array
    {
        if ($messageIds === []) {
            return [];
        }

        $linked = [];
        $rows = $this->db->select(
            'SELECT message_id, file_id FROM forum_message_files WHERE message_id IN (' . $this->placeholders($messageIds) . ') ORDER BY id',
            $messageIds
        );
        foreach ($rows as $row) {
            $linked[(int) $row['message_id']][] = (int) $row['file_id'];
        }

        return $linked;
    }

    /**
     * Paths of the files of the forum the text shows, relative to the disk.
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
     * One file of the forum for each stored path: the linked one when there is, otherwise the first
     * file of that path.
     *
     * @param list<int> $linked
     * @param list<string> $paths
     * @return list<int>
     */
    private function files(array $linked, array $paths): array
    {
        $byPath = [];
        $linkedSet = array_flip($linked);
        $rows = [];
        if ($linked !== []) {
            $rows = $this->db->select(
                'SELECT id, path FROM files WHERE deleted_at IS NULL AND id IN (' . $this->placeholders($linked) . ')',
                $linked
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
            if (! isset($byPath[$path]) || (isset($linkedSet[$id]) && ! isset($linkedSet[$byPath[$path]]))) {
                $byPath[$path] = $id;
            }
        }

        return array_values($byPath);
    }

    /** The file itself when no post holds it yet, otherwise a new row over the same stored file. */
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

    private function register(int $fileId, int $messageId, int $userId, string $now): void
    {
        $this->db->execute(
            'INSERT INTO editor_uploads (file_id, user_id, directory, owner_type, owner_id, created_at) VALUES (?, ?, ?, ?, ?, ?)',
            [$fileId, $userId, rtrim(self::DIRECTORY, '/'), self::OWNER_TYPE, $messageId, $now]
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
