<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Infrastructure\Persistence\Repository;

use Johncms\Database\ConnectionInterface;
use Johncms\Modules\News\Application\Exceptions\NewsLegacyImportException;
use Johncms\Modules\News\Domain\Repository\NewsLegacyRepositoryInterface;

final readonly class SqlNewsLegacyRepository implements NewsLegacyRepositoryInterface
{
    public function __construct(private ConnectionInterface $db)
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
        foreach (['news_comments', 'news_votes'] as $table) {
            $conflict = $this->db->selectOne("SELECT id FROM $table WHERE article_id = ? AND item_id IS NOT NULL AND item_id <> ? LIMIT 1", [$articleId, $itemId]);
            if ($conflict !== null) {
                throw new NewsLegacyImportException("Conflicting discussion mapping for article $articleId in $table.");
            }
            $this->db->execute("UPDATE $table SET item_id = ? WHERE article_id = ?", [$itemId, $articleId]);
        }
    }

    public function verifyDiscussions(int $articleId, int $itemId): void
    {
        foreach (['news_comments', 'news_votes'] as $table) {
            if ($this->db->selectOne("SELECT id FROM $table WHERE article_id = ? AND (item_id IS NULL OR item_id <> ?) LIMIT 1", [$articleId, $itemId]) !== null) {
                throw new NewsLegacyImportException("Incomplete discussion mapping for article $articleId in $table.");
            }
        }
    }

    public function queueFiles(array $fileIds): void
    {
        foreach (array_unique($fileIds) as $id) {
            if ($this->db->selectOne('SELECT file_id FROM news_legacy_cleanup_files WHERE file_id = ?', [$id]) === null) {
                $this->db->execute('INSERT INTO news_legacy_cleanup_files (file_id) VALUES (?)', [$id]);
            }
        }
    }
}
