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
     * The replies of the staff become entries that answer the entry they were written under.
     *
     * A reply keeps its text, its guestbook and its time; one without a time is put right after the
     * entry. The old engine kept only the name of the one who replied: the reply is given to the
     * account of that name when exactly one account has it, and stays without an account otherwise —
     * it is never given to the author of the entry. The accounts are counted the entries they now
     * have. A picture only the reply shows moves to the new entry; one both show is registered for
     * it as a copy, a row of its own over the same stored file, so deleting one never takes the
     * picture of the other. The permission to reply is gone with the replies.
     */
    public function up(): void
    {
        $now = date('Y-m-d H:i:s');
        $accounts = [];
        $counted = [];
        $lastId = 0;

        do {
            $rows = $this->db->select(
                "SELECT id, adm, time, text, admin, otvet, otime FROM guest WHERE id > ? AND otvet <> '' ORDER BY id LIMIT " . self::CHUNK,
                [$lastId]
            );

            foreach ($rows as $row) {
                $lastId = (int) $row['id'];
                $reply = (string) $row['otvet'];
                if (trim($reply) === '') {
                    continue;
                }

                $name = (string) $row['admin'];
                $userId = $accounts[$name] ??= $this->account($name);
                $time = (int) $row['otime'] > 0 ? (int) $row['otime'] : (int) $row['time'] + 1;

                $this->db->execute(
                    'INSERT INTO guest (adm, time, user_id, name, text, ip, browser, admin, otvet, otime, edit_who, edit_time, edit_count, reply_to_id)'
                    . " VALUES (?, ?, ?, ?, ?, 0, '', '', '', 0, '', 0, 0, ?)",
                    [(int) $row['adm'], $time, $userId, $name, $reply, $lastId]
                );
                $answer = (int) ($this->db->selectOne('SELECT MAX(id) AS id FROM guest WHERE reply_to_id = ?', [$lastId])['id'] ?? 0);

                $this->movePictures($lastId, $answer, (string) $row['text'], $reply, $now);
                if ($userId > 0) {
                    $counted[$userId] = ($counted[$userId] ?? 0) + 1;
                }
            }
        } while (count($rows) === self::CHUNK);

        foreach ($counted as $userId => $count) {
            $this->db->execute('UPDATE users SET postguest = postguest + ? WHERE id = ?', [$count, $userId]);
        }
        $this->db->execute('DELETE FROM role_permissions WHERE permission = ?', ['guestbook.entry.reply']);
    }

    /** The only account of that name, or 0 when none or several have it. */
    private function account(string $name): int
    {
        if ($name === '') {
            return 0;
        }

        $accounts = $this->db->select('SELECT id FROM users WHERE name = ? LIMIT 2', [$name]);

        return count($accounts) === 1 ? (int) $accounts[0]['id'] : 0;
    }

    private function movePictures(int $entryId, int $answerId, string $text, string $reply, string $now): void
    {
        $inReply = $this->shownPaths($reply);
        if ($inReply === [] || $answerId === 0) {
            return;
        }

        $inText = array_flip($this->shownPaths($text));
        $owned = $this->db->select(
            'SELECT u.id, u.file_id, u.user_id, f.path FROM editor_uploads u JOIN files f ON f.id = u.file_id WHERE u.owner_type = ? AND u.owner_id = ?',
            [self::OWNER_TYPE, $entryId]
        );

        foreach ($owned as $upload) {
            $path = (string) $upload['path'];
            if (! in_array($path, $inReply, true)) {
                continue;
            }

            if (! isset($inText[$path])) {
                $this->db->execute('UPDATE editor_uploads SET owner_id = ? WHERE id = ?', [$answerId, (int) $upload['id']]);
                continue;
            }

            $this->db->execute(
                'INSERT INTO files (storage, name, path, size, md5, sha1, created_at, updated_at)'
                . ' SELECT storage, name, path, size, md5, sha1, ?, ? FROM files WHERE id = ?',
                [$now, $now, (int) $upload['file_id']]
            );
            $copy = $this->db->selectOne(
                'SELECT MAX(c.id) AS id FROM files c JOIN files o ON o.storage = c.storage AND o.path = c.path WHERE o.id = ?',
                [(int) $upload['file_id']]
            );
            $this->db->execute(
                'INSERT INTO editor_uploads (file_id, user_id, directory, owner_type, owner_id, created_at) VALUES (?, ?, ?, ?, ?, ?)',
                [(int) ($copy['id'] ?? 0), (int) $upload['user_id'], rtrim(self::DIRECTORY, '/'), self::OWNER_TYPE, $answerId, $now]
            );
        }
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
};
