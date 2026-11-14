<?php

declare(strict_types=1);

use Dom\HTMLDocument;
use Johncms\Database\Migrations\Migration;

return new class extends Migration {
    private const string TYPE = 'news';
    private const string DIRECTORY = 'news_comments/';
    private const int CHUNK = 500;

    public function useTransaction(): bool
    {
        return true;
    }

    /**
     * The comments of the articles move into the comments of the core.
     *
     * Only those of the articles already mapped to the collection: the comments of the news of
     * 9.x still waiting for system:update keep their article and are carried by its import. What
     * was deleted is not carried. The metadata of the author becomes columns, the name is the one
     * of the account, the smilies of the staff render for the accounts holding a role, and the
     * authors are counted the comments they now have. The pictures of a comment — the files its
     * list of attachments named and the files of `news_comments` its text shows — are held by the
     * comment in the registry of the editor uploads; a file an earlier comment already holds gets
     * a row of its own over the same stored file, and a file the articles link stays with them.
     */
    public function up(): void
    {
        $now = date('Y-m-d H:i:s');
        $staff = null;
        $counts = [];

        do {
            $rows = $this->db->select(
                'SELECT id, item_id, user_id, text, user_data, created_at, attached_files FROM news_comments'
                . ' WHERE item_id IS NOT NULL AND deleted_at IS NULL ORDER BY id LIMIT ' . self::CHUNK
            );
            $names = $this->names($rows);
            if ($rows !== []) {
                $staff ??= $this->staff();
            }

            foreach ($rows as $row) {
                $userId = (int) $row['user_id'];
                $metadata = json_decode((string) $row['user_data'], true);
                $metadata = is_array($metadata) ? $metadata : [];
                $this->db->execute(
                    'INSERT INTO comments (subject_type, subject_id, user_id, text, author_name, author_ip, author_ip_via_proxy,'
                    . ' author_user_agent, staff_smilies, created_at, edit_count) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)',
                    [
                        self::TYPE,
                        (int) $row['item_id'],
                        $userId,
                        (string) $row['text'],
                        $names[$userId] ?? '',
                        $this->value($metadata['ip'] ?? null, 45),
                        $this->value($metadata['ip_via_proxy'] ?? null, 45),
                        $this->value($metadata['user_agent'] ?? null, 65535),
                        isset($staff[$userId]) ? 1 : 0,
                        (string) $row['created_at'],
                    ]
                );
                $commentId = (int) ($this->db->selectOne('SELECT MAX(id) AS id FROM comments')['id'] ?? 0);

                $files = $this->files($this->attachedIds((string) $row['attached_files']), $this->shownPaths((string) $row['text']));
                foreach ($files as $fileId) {
                    $this->db->execute(
                        'INSERT INTO editor_uploads (file_id, user_id, directory, owner_type, owner_id, created_at) VALUES (?, ?, ?, ?, ?, ?)',
                        [$this->ownFile($fileId, $now), $userId, rtrim(self::DIRECTORY, '/'), 'comment', $commentId, $now]
                    );
                }

                $this->db->execute('DELETE FROM news_comments WHERE id = ?', [(int) $row['id']]);
                if ($userId > 0) {
                    $counts[$userId] = ($counts[$userId] ?? 0) + 1;
                }
            }
        } while (count($rows) === self::CHUNK);

        // A deleted comment of a mapped article has nothing left to carry.
        $this->db->execute('DELETE FROM news_comments WHERE item_id IS NOT NULL');
        foreach ($counts as $userId => $count) {
            $this->db->execute('UPDATE users SET komm = komm + ? WHERE id = ?', [$count, $userId]);
        }
    }

    /** @return array<int, true> */
    private function staff(): array
    {
        $staff = [];
        foreach ($this->db->select('SELECT DISTINCT user_id FROM user_roles WHERE expires_at IS NULL OR expires_at > ?', [time()]) as $row) {
            $staff[(int) $row['user_id']] = true;
        }

        return $staff;
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return array<int, string>
     */
    private function names(array $rows): array
    {
        $ids = array_values(array_unique(array_map(static fn(array $row): int => (int) $row['user_id'], $rows)));
        if ($ids === []) {
            return [];
        }

        $names = [];
        foreach ($this->db->select('SELECT id, name FROM users WHERE id IN (' . $this->placeholders($ids) . ')', $ids) as $user) {
            $names[(int) $user['id']] = (string) $user['name'];
        }

        return $names;
    }

    private function value(mixed $value, int $length): ?string
    {
        if (! is_scalar($value) || (string) $value === '') {
            return null;
        }

        return mb_substr((string) $value, 0, $length);
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

        $result = [];
        foreach ($ids as $id) {
            if (is_numeric($id) && (int) $id > 0) {
                $result[(int) $id] = true;
            }
        }

        return array_keys($result);
    }

    /**
     * Paths of the files of the comments the text shows, relative to the disk.
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
     * One file of the comments for each stored path, the attached one first; a file the articles
     * link is left to them.
     *
     * @param list<int> $attached
     * @param list<string> $paths
     * @return list<int>
     */
    private function files(array $attached, array $paths): array
    {
        $rows = [];
        if ($attached !== []) {
            $rows = $this->db->select('SELECT id, path FROM files WHERE deleted_at IS NULL AND id IN (' . $this->placeholders($attached) . ')', $attached);
        }
        if ($paths !== []) {
            $rows = [
                ...$rows,
                ...$this->db->select('SELECT id, path FROM files WHERE deleted_at IS NULL AND path IN (' . $this->placeholders($paths) . ') ORDER BY id', $paths),
            ];
        }

        $attachedSet = array_flip($attached);
        $byPath = [];
        foreach ($rows as $row) {
            $path = (string) $row['path'];
            $id = (int) $row['id'];
            if (! str_starts_with($path, self::DIRECTORY) || $this->linkedByArticles($id)) {
                continue;
            }
            if (! isset($byPath[$path]) || (isset($attachedSet[$id]) && ! isset($attachedSet[$byPath[$path]]))) {
                $byPath[$path] = $id;
            }
        }

        return array_values($byPath);
    }

    private function linkedByArticles(int $fileId): bool
    {
        return $this->db->selectOne('SELECT file_id FROM collection_file_links WHERE file_id = ? LIMIT 1', [$fileId]) !== null
            || $this->db->selectOne('SELECT file_id FROM collection_file_value_links WHERE file_id = ? LIMIT 1', [$fileId]) !== null;
    }

    /** The file itself when no comment holds it yet, otherwise a new row over the same stored file. */
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

    /**
     * @param list<int|string> $values
     */
    private function placeholders(array $values): string
    {
        return implode(', ', array_fill(0, count($values), '?'));
    }
};
