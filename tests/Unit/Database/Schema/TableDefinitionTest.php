<?php

declare(strict_types=1);

namespace Tests\Unit\Database\Schema;

use Johncms\Database\Schema\ColumnType;
use Johncms\Database\Schema\IndexType;
use Johncms\Database\Schema\ReferentialAction;
use Johncms\Database\Schema\SchemaDefinitionException;
use Johncms\Database\Schema\TableDefinition;
use PHPUnit\Framework\TestCase;

/**
 * The description on its own, with no database anywhere near it: what the migration asked for is
 * what the adapter later reads.
 */
final class TableDefinitionTest extends TestCase
{
    public function testColumnsKeepTheirOrderAndType(): void
    {
        $table = new TableDefinition('forum_sections');
        $table->increments('id');
        $table->string('name');
        $table->text('description');

        $columns = $table->getColumns();

        self::assertCount(3, $columns);
        self::assertSame(['id', 'name', 'description'], array_map(static fn ($column): string => $column->name, $columns));
        self::assertSame(ColumnType::Increments, $columns[0]->type);
        self::assertSame(ColumnType::String, $columns[1]->type);
        self::assertSame(ColumnType::Text, $columns[2]->type);
    }

    public function testModifiersAreRecordedOnTheColumn(): void
    {
        $table = new TableDefinition('cms_ads');
        $table->integer('count')->unsigned()->nullable()->default(0)->comment('Shows so far');

        $column = $table->getColumns()[0];

        self::assertTrue($column->isUnsigned());
        self::assertTrue($column->isNullable());
        self::assertTrue($column->hasDefault());
        self::assertSame(0, $column->getDefault());
        self::assertSame('Shows so far', $column->getComment());
        self::assertFalse($column->isChange());
    }

    /**
     * A default of null is a value, not the absence of one: without the distinction a column
     * declared DEFAULT NULL and a column declared with no default look the same.
     */
    public function testNullIsAValidDefault(): void
    {
        $table = new TableDefinition('users');
        $table->string('nickname')->default(null);
        $table->string('mail');

        self::assertTrue($table->getColumns()[0]->hasDefault());
        self::assertNull($table->getColumns()[0]->getDefault());
        self::assertFalse($table->getColumns()[1]->hasDefault());
    }

    public function testAKeyDeclaredOnAColumnLandsAmongTheKeys(): void
    {
        $table = new TableDefinition('forum_sections');
        $table->string('slug')->unique();
        $table->integer('parent')->index();

        $indexes = $table->getIndexes();

        self::assertCount(2, $indexes);
        self::assertSame(IndexType::Unique, $indexes[0]->type);
        self::assertSame(['slug'], $indexes[0]->columns);
        self::assertSame(IndexType::Index, $indexes[1]->type);
        self::assertSame(['parent'], $indexes[1]->columns);
    }

    public function testACompositeKeyKeepsItsColumns(): void
    {
        $table = new TableDefinition('user_roles');
        $table->unique(['user_id', 'role_id'], 'user_role');

        $index = $table->getIndexes()[0];

        self::assertSame(['user_id', 'role_id'], $index->columns);
        self::assertSame('user_role', $index->name);
    }

    public function testAWordIndexIsRecordedLikeAnyOtherKey(): void
    {
        $table = new TableDefinition('forum_messages');
        $table->longText('text');
        $table->fullText('text', 'text');

        $index = $table->getIndexes()[0];

        self::assertSame(IndexType::FullText, $index->type);
        self::assertSame(['text'], $index->columns);
        self::assertSame('text', $index->name);
    }

    public function testTimestampsAddTwoNullableColumns(): void
    {
        $table = new TableDefinition('news_articles');
        $table->timestamps();

        $columns = $table->getColumns();

        self::assertSame(['created_at', 'updated_at'], array_map(static fn ($column): string => $column->name, $columns));

        foreach ($columns as $column) {
            self::assertSame(ColumnType::Timestamp, $column->type);
            self::assertTrue($column->isNullable());
        }
    }

    public function testSoftDeletesAddOneNullableColumn(): void
    {
        $table = new TableDefinition('files');
        $table->softDeletes();

        $column = $table->getColumns()[0];

        self::assertSame('deleted_at', $column->name);
        self::assertTrue($column->isNullable());
    }

    public function testForeignKeyIsBuiltFluently(): void
    {
        $table = new TableDefinition('news_comments');
        $table->foreign('article_id')
            ->references('id')
            ->on('news_articles')
            ->onUpdate(ReferentialAction::Cascade)
            ->onDelete(ReferentialAction::Cascade);

        $foreignKey = $table->getForeignKeys()[0];

        self::assertSame(['article_id'], $foreignKey->columns);
        self::assertSame(['id'], $foreignKey->getReferencedColumns());
        self::assertSame('news_articles', $foreignKey->getReferencedTable());
        self::assertSame(ReferentialAction::Cascade, $foreignKey->getOnUpdate());
        self::assertSame(ReferentialAction::Cascade, $foreignKey->getOnDelete());
    }

    public function testAForeignKeyWithoutATableRefusesToBeRead(): void
    {
        $table = new TableDefinition('news_comments');
        $table->foreign('article_id')->references('id');

        $this->expectException(SchemaDefinitionException::class);

        $table->getForeignKeys()[0]->getReferencedTable();
    }

    public function testRemovalsAndRenamesAreRecordedSeparately(): void
    {
        $table = new TableDefinition('users');
        $table->dropColumn('rights', 'set_forum');
        $table->renameColumn('preg', 'confirmed');
        $table->dropIndex('users_mail_index');
        $table->dropUnique('users_name_unique');
        $table->dropForeign('users_avatar_id_foreign');

        self::assertSame(['rights', 'set_forum'], $table->getDroppedColumns());
        self::assertSame([['from' => 'preg', 'to' => 'confirmed']], $table->getRenamedColumns());
        self::assertSame(['users_avatar_id_foreign'], $table->getDroppedForeignKeys());

        $dropped = $table->getDroppedIndexes();
        self::assertCount(2, $dropped);
        self::assertSame(IndexType::Index, $dropped[0]->type);
        self::assertSame('users_mail_index', $dropped[0]->name);
        self::assertSame(IndexType::Unique, $dropped[1]->type);
        self::assertSame('users_name_unique', $dropped[1]->name);
    }
}
