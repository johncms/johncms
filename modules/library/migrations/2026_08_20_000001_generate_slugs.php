<?php

/**
 * Gives the sections and the articles the slugs their addresses are built from.
 *
 * Only a site upgrading from 9.9 has anything to do here: it reaches this with tables that have
 * no slug column at all, while a fresh installation was built with one and has no rows yet.
 *
 * A row that already carries a slug keeps it. What is generated is only what is missing, and the
 * slugs already in the table are reserved first, so nothing collides with a section somebody
 * named by hand.
 */

declare(strict_types=1);

use Illuminate\Support\Str;
use Johncms\Database\Migrations\Migration;
use Johncms\Database\Schema\TableDefinition;

return new class extends Migration {
    public function up(): void
    {
        $this->addSlugColumn('library_cats');
        $this->addUniqueIndex('library_cats', 'library_cats_parent_slug_unique', ['parent', 'slug']);
        $this->fillSlugs('library_cats', 'parent', 'section');

        $this->addSlugColumn('library_texts');
        $this->fillSlugs('library_texts', 'cat_id', 'article');
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

    /**
     * @param list<string> $columns
     */
    private function addUniqueIndex(string $table, string $name, array $columns): void
    {
        if ($this->schema->hasIndex($table, $name)) {
            return;
        }

        $this->schema->alter($table, static function (TableDefinition $definition) use ($name, $columns): void {
            $definition->unique($columns, $name);
        });
    }

    /**
     * A slug is unique among the rows that share a parent, so two sections of different parents
     * may both be called the same thing.
     *
     * Str::slug is what the site itself makes a slug with; a row that has to be converted is on a
     * site still running the release that came with it. A fresh installation has no rows here, so
     * the loop below never runs and the helper is never reached.
     */
    private function fillSlugs(string $table, string $scopeColumn, string $fallback): void
    {
        $taken = [];

        foreach ($this->db->select("SELECT {$scopeColumn} AS scope, slug FROM {$table} WHERE slug IS NOT NULL AND slug <> ''") as $row) {
            $taken[$row['scope'] . ':' . $row['slug']] = true;
        }

        $rows = $this->db->select(
            "SELECT id, {$scopeColumn} AS scope, name FROM {$table} WHERE slug IS NULL OR slug = '' ORDER BY id"
        );

        foreach ($rows as $row) {
            $base = Str::slug((string) $row['name']);
            if ($base === '') {
                $base = $fallback;
            }

            $slug = $base;
            $suffix = 2;
            while (isset($taken[$row['scope'] . ':' . $slug])) {
                $slug = $base . '-' . $suffix;
                ++$suffix;
            }

            $taken[$row['scope'] . ':' . $slug] = true;
            $this->db->execute("UPDATE {$table} SET slug = ? WHERE id = ?", [$slug, $row['id']]);
        }
    }
};
