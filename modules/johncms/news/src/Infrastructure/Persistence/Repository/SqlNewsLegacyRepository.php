<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Infrastructure\Persistence\Repository;

use Johncms\Database\ConnectionInterface;
use Johncms\Database\Schema\SchemaInterface;
use Johncms\Modules\News\Application\Exceptions\NewsLegacyImportException;
use Johncms\Modules\News\Domain\Repository\NewsLegacyRepositoryInterface;

final readonly class SqlNewsLegacyRepository implements NewsLegacyRepositoryInterface
{
    public function __construct(private ConnectionInterface $db, private SchemaInterface $schema)
    {
    }

    public function sections(): array
    {
        return $this->db->select('SELECT * FROM news_sections ORDER BY id');
    }

    public function articles(): array
    {
        return $this->db->select('SELECT * FROM news_articles ORDER BY id');
    }

    public function mapDiscussions(int $articleId, int $itemId): void
    {
        foreach ($this->discussionTables() as $table) {
            $conflict = $this->db->selectOne("SELECT id FROM $table WHERE article_id = ? AND item_id IS NOT NULL AND item_id <> ? LIMIT 1", [$articleId, $itemId]);
            if ($conflict !== null) {
                throw new NewsLegacyImportException("Conflicting discussion mapping for article $articleId in $table.");
            }
            $this->db->execute("UPDATE $table SET item_id = ? WHERE article_id = ?", [$itemId, $articleId]);
        }
    }

    public function verifyDiscussions(int $articleId, int $itemId): void
    {
        foreach ($this->discussionTables() as $table) {
            if ($this->db->selectOne("SELECT id FROM $table WHERE article_id = ? AND (item_id IS NULL OR item_id <> ?) LIMIT 1", [$articleId, $itemId]) !== null) {
                throw new NewsLegacyImportException("Incomplete discussion mapping for article $articleId in $table.");
            }
        }
    }

    public function commentsReferenceFile(int $fileId): bool
    {
        if (! $this->schema->hasTable('news_comments')) {
            return false;
        }
        foreach ($this->db->select('SELECT attached_files FROM news_comments WHERE attached_files LIKE ?', ['%' . $fileId . '%']) as $row) {
            $ids = json_decode((string) $row['attached_files'], true);
            if (is_array($ids) && in_array($fileId, array_map(intval(...), array_filter($ids, is_numeric(...))), true)) {
                return true;
            }
        }

        return false;
    }

    public function queueFiles(array $fileIds): void
    {
        foreach (array_unique($fileIds) as $id) {
            if ($this->db->selectOne('SELECT file_id FROM news_legacy_cleanup_files WHERE file_id = ?', [$id]) === null) {
                $this->db->execute('INSERT INTO news_legacy_cleanup_files (file_id) VALUES (?)', [$id]);
            }
        }
    }

    /**
     * The tables the discussions of the articles are mapped in; news_comments is gone once the
     * news of 9.x are imported.
     *
     * @return list<string>
     */
    private function discussionTables(): array
    {
        return $this->schema->hasTable('news_comments') ? ['news_comments', 'news_votes'] : ['news_votes'];
    }
}
