<?php

declare(strict_types=1);

use Johncms\Database\Migrations\Migration;

return new class extends Migration {
    private const string TYPE = 'album';
    private const int CHUNK = 500;

    public function useTransaction(): bool
    {
        return true;
    }

    /**
     * The comments of the photos move into the comments of the core.
     *
     * The replies of the owner and of the moderators are gone from the discussion: a stored reply
     * becomes a comment of its own, of its author and with its time, so no text is lost. A reply whose
     * attributes cannot be read keeps its text under an unknown author — it is never given to the
     * author of the comment it answered. The authors of the replies are counted the comments they now
     * have, and every photo its comments. What the old engine kept serialized goes into columns; an IP
     * it stored as a number becomes an address.
     */
    public function up(): void
    {
        $staff = $this->staff();
        $replies = [];
        $lastId = 0;

        do {
            $rows = $this->db->select(
                'SELECT id, sub_id, user_id, time, text, reply, attributes FROM cms_album_comments WHERE id > ? ORDER BY id LIMIT ' . self::CHUNK,
                [$lastId]
            );
            $names = $this->names($rows);
            $inserts = [];

            foreach ($rows as $row) {
                $lastId = (int) $row['id'];
                $attributes = $this->attributes((string) $row['attributes']);
                $userId = (int) $row['user_id'];
                $time = (int) $row['time'];

                $inserts[] = [
                    'subject_id'          => (int) $row['sub_id'],
                    'user_id'             => $userId,
                    'text'                => (string) $row['text'],
                    'author_name'         => (string) ($attributes['author_name'] ?? $names[$userId] ?? ''),
                    'author_ip'           => $this->ip($attributes['author_ip'] ?? null),
                    'author_ip_via_proxy' => $this->ip($attributes['author_ip_via_proxy'] ?? null),
                    'author_user_agent'   => isset($attributes['author_browser']) ? (string) $attributes['author_browser'] : null,
                    'staff_smilies'       => isset($staff[$userId]) ? 1 : 0,
                    'created_at'          => gmdate('Y-m-d H:i:s', $time),
                    'edited_at'           => empty($attributes['edit_time']) ? null : gmdate('Y-m-d H:i:s', (int) $attributes['edit_time']),
                    'edited_by'           => empty($attributes['edit_id']) ? null : (int) $attributes['edit_id'],
                    'editor_name'         => isset($attributes['edit_name']) ? (string) $attributes['edit_name'] : null,
                    'edit_count'          => (int) ($attributes['edit_count'] ?? 0),
                ];

                if (trim((string) $row['reply']) === '') {
                    continue;
                }

                // Attributes that cannot be read say nothing about who replied.
                $replyAuthor = $attributes === null ? 0 : (int) ($attributes['reply_id'] ?? 0);
                $inserts[] = [
                    'subject_id'          => (int) $row['sub_id'],
                    'user_id'             => $replyAuthor,
                    'text'                => (string) $row['reply'],
                    'author_name'         => (string) ($attributes['reply_name'] ?? ''),
                    'author_ip'           => null,
                    'author_ip_via_proxy' => null,
                    'author_user_agent'   => null,
                    'staff_smilies'       => $this->replyOfStaff($attributes) ? 1 : 0,
                    'created_at'          => gmdate('Y-m-d H:i:s', empty($attributes['reply_time']) ? $time + 1 : (int) $attributes['reply_time']),
                    'edited_at'           => null,
                    'edited_by'           => null,
                    'editor_name'         => null,
                    'edit_count'          => 0,
                ];
                if ($replyAuthor > 0) {
                    $replies[$replyAuthor] = ($replies[$replyAuthor] ?? 0) + 1;
                }
            }

            $this->insert($inserts);
        } while (count($rows) === self::CHUNK);

        $this->db->execute(
            'UPDATE cms_album_files SET comm_count = (SELECT COUNT(*) FROM comments WHERE comments.subject_type = ? AND comments.subject_id = cms_album_files.id)',
            [self::TYPE]
        );
        foreach ($replies as $userId => $count) {
            $this->db->execute('UPDATE users SET komm = komm + ? WHERE id = ?', [$count, $userId]);
        }
    }

    /**
     * The accounts holding a role: their smilies of the staff render, as the old engine showed them.
     *
     * @return array<int, true>
     */
    private function staff(): array
    {
        $staff = [];
        foreach ($this->db->select('SELECT DISTINCT user_id FROM user_roles WHERE expires_at IS NULL OR expires_at > ?', [time()]) as $row) {
            $staff[(int) $row['user_id']] = true;
        }

        return $staff;
    }

    /**
     * The names of the authors whose attributes do not carry one.
     *
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
        $placeholders = implode(', ', array_fill(0, count($ids), '?'));
        foreach ($this->db->select('SELECT id, name FROM users WHERE id IN (' . $placeholders . ')', $ids) as $user) {
            $names[(int) $user['id']] = (string) $user['name'];
        }

        return $names;
    }

    /**
     * @return array<string, mixed>|null Null when the attributes are damaged.
     */
    private function attributes(string $serialized): ?array
    {
        $attributes = @unserialize($serialized, ['allowed_classes' => false]);

        return is_array($attributes) ? $attributes : null;
    }

    private function ip(mixed $ip): ?string
    {
        if ($ip === null || $ip === '' || $ip === 0 || $ip === '0') {
            return null;
        }
        if (is_int($ip) || ctype_digit((string) $ip)) {
            $address = long2ip((int) $ip);

            return $address === '0.0.0.0' ? null : $address;
        }

        return substr((string) $ip, 0, 45);
    }

    /**
     * @param array<string, mixed>|null $attributes
     */
    private function replyOfStaff(?array $attributes): bool
    {
        if ($attributes === null) {
            return false;
        }
        if (isset($attributes['reply_staff'])) {
            return (bool) $attributes['reply_staff'];
        }

        return (int) ($attributes['reply_rights'] ?? 0) >= 1;
    }

    /**
     * @param list<array<string, mixed>> $rows
     */
    private function insert(array $rows): void
    {
        if ($rows === []) {
            return;
        }

        $columns = ['subject_type', ...array_keys($rows[0])];
        $values = [];
        $bindings = [];
        foreach ($rows as $row) {
            $values[] = '(' . implode(', ', array_fill(0, count($columns), '?')) . ')';
            array_push($bindings, self::TYPE, ...array_values($row));
        }

        $this->db->execute(
            'INSERT INTO comments (' . implode(', ', $columns) . ') VALUES ' . implode(', ', $values),
            $bindings
        );
    }
};
