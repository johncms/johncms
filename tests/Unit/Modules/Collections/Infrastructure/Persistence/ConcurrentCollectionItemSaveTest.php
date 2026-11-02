<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Collections\Infrastructure\Persistence;

use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Modules\Collections\Application\DTO\CollectionItemFormDTO;
use Johncms\Modules\Collections\Application\Exceptions\CollectionItemCodeAlreadyExistsException;
use Johncms\Modules\Collections\Domain\Exceptions\CollectionWriteConflictException;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use Tests\Support\CollectionItemWriterFactory;
use Tests\Support\CollectionWriterDatabase;
use Tests\Support\FunctionalDatabase;
use Tests\Support\RunsMigrations;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class ConcurrentCollectionItemSaveTest extends TestCase
{
    use RunsMigrations;

    private int $collection;

    protected function setUp(): void
    {
        if (! FunctionalDatabase::fromEnvironment()->isMysql()) {
            self::markTestSkipped('Concurrent row locking requires MySQL or MariaDB.');
        }
        $url = (string) getenv(FunctionalDatabase::VARIABLE);
        // Keep this test's committed fixtures separate from the transactional functional suite.
        $url = preg_replace('~/[^/]+$~', '/johncms_collection_writers_test', $url);
        putenv(FunctionalDatabase::VARIABLE . '=' . $url);
        FunctionalDatabase::fromEnvironment()->prepare();
        CollectionWriterDatabase::boot();
        $this->migrate('collections');
        $this->collection = Capsule::table('collections')->insertGetId(['code' => 'concurrent', 'name' => 'Concurrent']);
    }

    public function testConcurrentExplicitRootCodesCannotDuplicate(): void
    {
        $this->concurrent('hello', [0, 2]);
        self::assertSame(1, Capsule::table('collection_items')->count());
    }

    public function testConcurrentGeneratedRootCodesReceiveDifferentSuffixes(): void
    {
        $this->concurrent('', [0, 0]);
        self::assertSame(['writer', 'writer-2'], Capsule::table('collection_items')->orderBy('code')->pluck('code')->all());
    }

    public function testUniquenessUsesCurrentReadsInsideAnExistingSnapshot(): void
    {
        Capsule::connection()->beginTransaction();
        Capsule::table('collection_items')->count();
        [$process, $output] = $this->start('hello');
        self::assertSame(0, $this->finish($process, $output));
        try {
            CollectionItemWriterFactory::create()->execute(null, $this->form());
            self::fail('The committed code must be visible to the locking read.');
        } catch (CollectionItemCodeAlreadyExistsException) {
            self::assertSame(1, Capsule::connection()->transactionLevel());
        } catch (CollectionWriteConflictException) {
            self::assertTrue($this->snapshotIsolation(), 'Traditional current reads should see the committed code.');
        } finally {
            Capsule::connection()->rollBack();
        }
        self::assertSame(1, Capsule::table('collection_items')->count());
    }

    public function testUpdateDoesNotUseStaleModelAttributesFromTheOuterSnapshot(): void
    {
        $writer = CollectionItemWriterFactory::create();
        $id = $writer->execute(null, $this->form());
        Capsule::connection()->beginTransaction();
        Capsule::table('collection_items')->where('id', $id)->first();
        [$process, $output] = $this->start('hello', $id);
        self::assertSame(0, $this->finish($process, $output));
        try {
            $writer->execute($id, $this->form());
            Capsule::connection()->commit();
        } catch (CollectionWriteConflictException) {
            self::assertTrue($this->snapshotIsolation(), 'Traditional current reads should permit the update.');
            Capsule::connection()->rollBack();
            // Snapshot isolation requires retrying the entire transaction, not just the savepoint.
            $writer->execute($id, $this->form());
        }
        self::assertSame('Hello', Capsule::table('collection_items')->where('id', $id)->value('name'));
    }

    public function testNativeOuterTransactionCanRollbackTheSave(): void
    {
        $connection = new \Johncms\Database\PdoConnection(Capsule::connection()->getPdo());
        try {
            $connection->transaction(function (): void {
                CollectionItemWriterFactory::create()->execute(null, $this->form());
                throw new \RuntimeException('Rollback native transaction');
            });
        } catch (\RuntimeException $exception) {
            self::assertSame('Rollback native transaction', $exception->getMessage());
        }
        self::assertSame(0, Capsule::table('collection_items')->count());
    }

    public function testConcurrentViewsDoNotLoseIncrementsOrModifyUpdatedAt(): void
    {
        $id = CollectionItemWriterFactory::create()->execute(null, $this->form());
        Capsule::table('collection_items')->where('id', $id)->update(['updated_at' => '2000-01-01 00:00:00']);
        [$first, $firstOutput] = $this->start('50', $id, 'views');
        [$second, $secondOutput] = $this->start('50', $id, 'views');
        self::assertSame(0, $this->finish($first, $firstOutput));
        self::assertSame(0, $this->finish($second, $secondOutput));
        $item = Capsule::table('collection_items')->where('id', $id)->first();
        self::assertSame(100, $item->view_count);
        self::assertSame('2000-01-01 00:00:00', $item->updated_at);
    }

    public function testConcurrentPatchesPreserveTheOtherCustomField(): void
    {
        foreach (['first', 'second'] as $code) {
            Capsule::table('collection_fields')->insert(['collection_id' => $this->collection, 'code' => $code, 'name' => $code, 'type' => 'string']);
        }
        $id = CollectionItemWriterFactory::create()->execute(null, $this->form());
        Capsule::connection()->beginTransaction();
        Capsule::table('collections')->where('id', $this->collection)->lockForUpdate()->first();
        try {
            [$first, $firstOutput] = $this->start('first', $id, 'values');
            [$second, $secondOutput] = $this->start('second', $id, 'values');
            $this->connectionId($firstOutput);
            $this->connectionId($secondOutput);
        } finally {
            Capsule::connection()->rollBack();
        }
        self::assertSame(0, $this->finish($first, $firstOutput));
        self::assertSame(0, $this->finish($second, $secondOutput));
        $values = CollectionItemWriterFactory::api()->getItem($id)->values;
        ksort($values);
        self::assertSame(['first' => 'Patched', 'second' => 'Patched'], $values);
    }

    public function testConcurrentSectionRootCodesCannotDuplicate(): void
    {
        Capsule::connection()->beginTransaction();
        Capsule::table('collections')->where('id', $this->collection)->lockForUpdate()->first();
        try {
            [$first, $firstOutput] = $this->start('same', 0, 'section');
            [$second, $secondOutput] = $this->start('same', 0, 'section');
            $this->connectionId($firstOutput);
            $this->connectionId($secondOutput);
        } finally {
            Capsule::connection()->rollBack();
        }
        $codes = [$this->finish($first, $firstOutput), $this->finish($second, $secondOutput)];
        sort($codes);
        self::assertSame([0, 2], $codes);
        self::assertSame(1, Capsule::table('collection_sections')->count());
    }

    public function testConcurrentOppositeMovesCannotCreateACycle(): void
    {
        $writer = CollectionItemWriterFactory::sectionWriter();
        $firstId = $writer->execute(null, new \Johncms\Modules\Collections\Application\DTO\CollectionSectionFormDTO($this->collection, null, 'first', 'First', null, true, 100));
        $secondId = $writer->execute(null, new \Johncms\Modules\Collections\Application\DTO\CollectionSectionFormDTO($this->collection, null, 'second', 'Second', null, true, 100));
        Capsule::connection()->beginTransaction();
        Capsule::table('collections')->where('id', $this->collection)->lockForUpdate()->first();
        try {
            [$first, $firstOutput] = $this->start((string) $secondId, $firstId, 'move-section');
            [$second, $secondOutput] = $this->start((string) $firstId, $secondId, 'move-section');
            $this->connectionId($firstOutput);
            $this->connectionId($secondOutput);
        } finally {
            Capsule::connection()->rollBack();
        }
        $codes = [$this->finish($first, $firstOutput), $this->finish($second, $secondOutput)];
        sort($codes);
        self::assertSame([0, 2], $codes);
        self::assertSame(1, Capsule::table('collection_sections')->whereNull('parent')->count());
    }

    public function testSectionDeletionUsesCurrentContentsInsideAnExistingSnapshot(): void
    {
        $sections = CollectionItemWriterFactory::sectionsApi();
        $section = $sections->addSection(new \Johncms\Modules\Collections\Application\DTO\CollectionSectionFormDTO($this->collection, null, 'section', 'Section', null, true, 100));
        $preview = $sections->previewDeletion($this->collection, $section);
        Capsule::connection()->beginTransaction();
        Capsule::table('collection_items')->count();
        [$process, $output] = $this->start('new', $section, 'section-item');
        self::assertSame(0, $this->finish($process, $output));
        try {
            $sections->deleteSection($this->collection, $section, \Johncms\Modules\Collections\Domain\Enums\SectionDeletionMode::DeleteItems, expectedRevision: $preview->revision);
            self::fail('A new item was not part of the confirmed deletion.');
        } catch (\Johncms\Modules\Collections\Application\Exceptions\CollectionDeletionException $exception) {
            self::assertArrayHasKey('revision', $exception->errors);
        } catch (CollectionWriteConflictException) {
            self::assertTrue($this->snapshotIsolation());
        } finally {
            Capsule::connection()->rollBack();
        }
        self::assertNotNull($sections->findSection($this->collection, $section));
        self::assertSame(1, Capsule::table('collection_items')->where('section_id', $section)->count());
    }

    public function testConcurrentSectionDeleteAndItemSaveCannotCreateRootOrphans(): void
    {
        $sections = CollectionItemWriterFactory::sectionsApi();
        $section = $sections->addSection(new \Johncms\Modules\Collections\Application\DTO\CollectionSectionFormDTO($this->collection, null, 'section', 'Section', null, true, 100));
        Capsule::connection()->beginTransaction();
        Capsule::table('collections')->where('id', $this->collection)->lockForUpdate()->first();
        try {
            [$first, $firstOutput] = $this->start('new', $section, 'section-item');
            [$second, $secondOutput] = $this->start('delete', $section, 'delete-section');
            $this->connectionId($firstOutput);
            $this->connectionId($secondOutput);
        } finally {
            Capsule::connection()->rollBack();
        }
        self::assertContains($this->finish($first, $firstOutput), [0, 2]);
        self::assertSame(0, $this->finish($second, $secondOutput));
        self::assertNull($sections->findSection($this->collection, $section));
        self::assertSame(0, Capsule::table('collection_items')->count());
    }

    /** @param list<int> $expected */
    private function concurrent(string $code, array $expected): void
    {
        Capsule::connection()->beginTransaction();
        Capsule::table('collections')->where('id', $this->collection)->lockForUpdate()->first();
        try {
            [$first, $firstOutput] = $this->start($code);
            [$second, $secondOutput] = $this->start($code);
            // Neither writer can finish before both independent connections have started.
            $this->connectionId($firstOutput);
            $this->connectionId($secondOutput);
        } finally {
            Capsule::connection()->rollBack();
        }
        $codes = [$this->finish($first, $firstOutput), $this->finish($second, $secondOutput)];
        sort($codes);
        self::assertSame($expected, $codes);
    }

    /** @param resource $output */
    private function connectionId($output): int
    {
        $deadline = microtime(true) + 10;
        do {
            rewind($output);
            $line = fgets($output);
            if ($line !== false && ctype_digit(trim($line))) {
                return (int) trim($line);
            }
            usleep(10000);
        } while (microtime(true) < $deadline);
        self::fail('The concurrent writer did not connect.');
    }

    /** @return array{resource, resource} */
    private function start(string $code, ?int $id = null, ?string $mode = null): array
    {
        $output = tmpfile();
        self::assertIsResource($output);
        $arguments = [PHP_BINARY, ROOT_PATH . 'tests/Support/collection-item-writer.php', (string) $this->collection, $code];
        if ($id !== null) {
            $arguments[] = (string) $id;
        }
        if ($mode !== null) {
            $arguments[] = $mode;
        }
        $process = proc_open($arguments, [0 => ['pipe', 'r'], 1 => $output, 2 => $output], $pipes, ROOT_PATH);
        self::assertIsResource($process);
        fclose($pipes[0]);
        return [$process, $output];
    }

    /** @param resource $process
     * @param resource $output
     */
    private function finish($process, $output): int
    {
        $code = proc_close($process);
        rewind($output);
        $message = stream_get_contents($output);
        fclose($output);
        self::assertContains($code, [0, 2], $message === false ? '' : $message);
        return $code;
    }

    private function snapshotIsolation(): bool
    {
        $rows = Capsule::connection()->select("SHOW VARIABLES LIKE 'innodb_snapshot_isolation'");
        return $rows !== [] && in_array($rows[0]->Value, ['ON', '1', 1], true);
    }

    private function form(): CollectionItemFormDTO
    {
        return new CollectionItemFormDTO($this->collection, null, 'hello', 'Hello', true, null, null, 100, null, null, []);
    }
}
