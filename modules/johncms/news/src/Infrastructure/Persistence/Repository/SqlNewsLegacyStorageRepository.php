<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Infrastructure\Persistence\Repository;

use Johncms\Database\ConnectionInterface;
use Johncms\Database\Schema\SchemaInterface;
use Johncms\Database\Schema\TableDefinition;
use Johncms\Modules\News\Application\Exceptions\NewsLegacyImportException;
use Johncms\Modules\News\Domain\Repository\NewsLegacyRepositoryInterface;
use Johncms\Modules\News\Domain\Repository\NewsLegacyStorageRepositoryInterface;

final readonly class SqlNewsLegacyStorageRepository implements NewsLegacyStorageRepositoryInterface
{
    public function __construct(private ConnectionInterface $db, private SchemaInterface $schema, private NewsLegacyRepositoryInterface $legacy)
    {
    }

    public function prepare(): void
    {
        foreach (['news_comments', 'news_votes', 'news_search_index'] as $table) {
            if ($this->schema->hasForeignKeyForColumns($table, ['article_id'])) {
                $this->schema->alter($table, static fn (TableDefinition $definition) => $definition->dropForeignForColumns('article_id'));
            }
        }
    }

    public function hasData(): bool
    {
        foreach (['news_articles', 'news_sections'] as $table) {
            if ($this->schema->hasTable($table) && $this->db->selectOne("SELECT id FROM $table LIMIT 1") !== null) {
                if (! $this->schema->hasTable('news_articles') || ! $this->schema->hasTable('news_sections')) {
                    throw new NewsLegacyImportException('Legacy news tables are incomplete. Restore the backup before retrying system:update.');
                }
                return true;
            }
        }
        return false;
    }

    public function clearLegacyRows(): void
    {
        $this->db->execute('DELETE FROM news_articles');
        $this->db->execute('DELETE FROM news_sections');
    }

    public function removeOrphanDiscussions(): void
    {
        $this->db->transaction(function (): void {
            $files = [];
            foreach ($this->db->select('SELECT attached_files FROM news_comments WHERE item_id IS NULL') as $row) {
                $json = $row['attached_files'];
                $ids = json_decode(json: $json === null || $json === '' ? '[]' : (string) $json, associative: true, flags: JSON_THROW_ON_ERROR);
                if (! is_array($ids) || ! array_is_list($ids)) {
                    throw new NewsLegacyImportException('Repair malformed attachments in orphan news comments before retrying system:update.');
                }
                foreach ($ids as $id) {
                    if ((is_int($id) || (is_string($id) && ctype_digit($id))) && (int) $id > 0) {
                        $files[] = (int) $id;
                    } else {
                        throw new NewsLegacyImportException('Repair invalid file IDs in orphan news comments before retrying system:update.');
                    }
                }
            }
            $this->legacy->queueFiles($files);
            $this->db->execute('DELETE FROM news_comments WHERE item_id IS NULL');
            $this->db->execute('DELETE FROM news_votes WHERE item_id IS NULL');
        });
    }

    public function pendingFiles(): array
    {
        return array_map(static fn (array $row): int => (int) $row['file_id'], $this->db->select('SELECT file_id FROM news_legacy_cleanup_files ORDER BY file_id LIMIT 500'));
    }

    public function acknowledgeFile(int $id): void
    {
        $this->db->execute('DELETE FROM news_legacy_cleanup_files WHERE file_id = ?', [$id]);
    }

    public function dropLegacySchema(): void
    {
        foreach (['news_comments', 'news_votes'] as $table) {
            if (! $this->schema->hasColumn($table, 'article_id')) {
                continue;
            }
            $hasUnique = $table === 'news_votes' && $this->schema->hasIndex($table, 'article_user');
            $hasIndex = $this->schema->hasIndex($table, $table . '_article_id_index');
            $this->schema->alter($table, static function (TableDefinition $definition) use ($table, $hasUnique, $hasIndex): void {
                if ($hasUnique) {
                    $definition->dropUnique('article_user');
                }
                if ($hasIndex) {
                    $definition->dropIndex($table . '_article_id_index');
                }
                $definition->dropColumn('article_id');
            });
        }
        foreach (['news_search_index', 'news_articles', 'news_sections'] as $table) {
            $this->schema->dropIfExists($table);
        }
    }

    public function hasLegacySchema(): bool
    {
        return $this->schema->hasTable('news_articles') || $this->schema->hasTable('news_sections') || $this->schema->hasTable('news_search_index')
            || $this->schema->hasColumn('news_comments', 'article_id') || $this->schema->hasColumn('news_votes', 'article_id');
    }
}
