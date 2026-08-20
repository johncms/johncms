<?php

/**
 * Gives the categories and the files the slugs their addresses are built from.
 *
 * Only a site upgrading from 9.9 has anything to do here: it reaches this with tables that have
 * no slug column at all, while a fresh installation was built with one and has no rows yet.
 *
 * A row that already carries a slug keeps it. What is generated is only what is missing, and the
 * slugs already in the table are reserved first, so nothing collides with a category somebody
 * named by hand.
 */

declare(strict_types=1);

use Illuminate\Support\Str;
use Johncms\Database\Migrations\Migration;
use Johncms\Database\Schema\TableDefinition;

return new class extends Migration {
    /**
     * Addresses of the module itself. A category that ended up with one of these would shadow the
     * page it belongs to, so it is given a suffix instead.
     *
     * @var list<string>
     */
    private const array RESERVED_CATEGORY_SLUGS = [
        'new',
        'top',
        'search',
        'favorites',
        'user-files',
        'load',
        'comments',
        'upload',
        'moderation',
        'edit-file',
        'delete-file',
        'edit-screen',
        'additional-files',
        'move-file',
        'import',
        'scan-dir',
        'recount',
        'top-users',
        'comments-review',
        'categories',
    ];

    public function up(): void
    {
        $this->addSlugColumn('download__category');
        $this->fillSlugs('download__category', 'section', reserved: true);
        $this->addUniqueIndex('download__category', 'download__category_refid_slug_unique');

        $this->addSlugColumn('download__files');
        $this->fillSlugs('download__files', 'file', reserved: false);
        $this->addUniqueIndex('download__files', 'download__files_refid_slug_unique');
    }

    private function addSlugColumn(string $table): void
    {
        if ($this->schema->hasColumn($table, 'slug')) {
            return;
        }

        $this->schema->alter($table, static function (TableDefinition $definition): void {
            $definition->string('slug')->nullable();
        });
    }

    private function addUniqueIndex(string $table, string $name): void
    {
        if ($this->schema->hasIndex($table, $name)) {
            return;
        }

        $this->schema->alter($table, static function (TableDefinition $definition) use ($name): void {
            $definition->unique(['refid', 'slug'], $name);
        });
    }

    /**
     * A slug is unique among the rows of one category, so two files in different categories may
     * both be called the same thing.
     *
     * Str::slug is what the site itself makes a slug with; a row that has to be converted is on a
     * site still running the release that came with it. A fresh installation has no rows here, so
     * the loop below never runs and the helper is never reached.
     */
    private function fillSlugs(string $table, string $fallback, bool $reserved): void
    {
        $taken = [];

        foreach ($this->db->select("SELECT refid, slug FROM {$table} WHERE slug IS NOT NULL AND slug <> ''") as $row) {
            $taken[$row['refid'] . ':' . $row['slug']] = true;
        }

        $rows = $this->db->select(
            "SELECT id, refid, rus_name FROM {$table} WHERE slug IS NULL OR slug = '' ORDER BY refid, id"
        );

        foreach ($rows as $row) {
            $base = Str::slug((string) $row['rus_name']);
            if ($base === '') {
                $base = $fallback . '-' . $row['id'];
            }

            if ($reserved && in_array($base, self::RESERVED_CATEGORY_SLUGS, true)) {
                $base .= '-section';
            }

            $slug = $base;
            $suffix = 2;
            while (isset($taken[$row['refid'] . ':' . $slug])) {
                $slug = $base . '-' . $suffix;
                ++$suffix;
            }

            $taken[$row['refid'] . ':' . $slug] = true;
            $this->db->execute("UPDATE {$table} SET slug = ? WHERE id = ?", [$slug, $row['id']]);
        }
    }
};
