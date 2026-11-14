<?php

declare(strict_types=1);

use Johncms\Database\Migrations\Migration;

return new class extends Migration {
    public function useTransaction(): bool
    {
        return true;
    }

    /**
     * Every guestbook carried over from the old table starts read. Its ids say nothing about the
     * order the entries were written in, and a reply of a moderator to an entry read long ago has
     * a newer time than the mark the old counter kept: the unread number cannot be carried over
     * exactly. What was unread at the moment of the update is not announced any more; the entries
     * themselves stay.
     */
    public function up(): void
    {
        $this->db->execute(
            "INSERT INTO profile__guestbook_reads (user_id, seen_comment_id)
             SELECT subject_id, MAX(id) FROM comments WHERE subject_type = 'profile_guestbook' GROUP BY subject_id"
        );
    }
};
