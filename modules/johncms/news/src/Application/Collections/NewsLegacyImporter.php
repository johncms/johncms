<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\Collections;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Support\Str;
use Johncms\Files\FileStore;
use Johncms\Modules\Collections\Application\Api\CollectionFileReferencesApiInterface;
use Johncms\Modules\Collections\Application\Api\CollectionItemRelationsApiInterface;
use Johncms\Modules\Collections\Application\Api\CollectionSectionsApiInterface;
use Johncms\Modules\Collections\Application\Api\CollectionsApiInterface;
use Johncms\Modules\Collections\Application\Api\CollectionsImportApiInterface;
use Johncms\Modules\Collections\Application\DTO\CollectionItemFormDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionItemHistoryDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionSectionFormDTO;
use Johncms\Modules\Collections\Application\Exceptions\CollectionItemFormException;
use Johncms\Modules\Collections\Application\Exceptions\CollectionSectionFormException;
use Johncms\Modules\Collections\Domain\Query\ContentCollectionItemQuery;
use Johncms\Modules\News\Application\DTO\NewsLegacyImportReportDTO;
use Johncms\Modules\News\Application\Exceptions\NewsLegacyImportException;
use Johncms\Modules\News\Domain\Repository\NewsDiscussionRepositoryInterface;
use Johncms\Modules\News\Domain\Repository\NewsLegacyRepositoryInterface;
use Johncms\Updates\SystemOperationContext;

/** Data phase only. The caller removes legacy rows in the same outer transaction after verification. */
final readonly class NewsLegacyImporter
{
    public function __construct(
        private NewsLegacyRepositoryInterface $legacy,
        private CollectionsApiInterface $collections,
        private CollectionsImportApiInterface $import,
        private CollectionSectionsApiInterface $sections,
        private CollectionItemRelationsApiInterface $relations,
        private CollectionFileReferencesApiInterface $itemFiles,
        private FileStore $files,
        private NewsDiscussionRepositoryInterface $discussion,
        private NewsCollectionOwnerUrls $urls,
        private SystemOperationContext $context,
    ) {
    }

    public function import(): NewsLegacyImportReportDTO
    {
        $this->context->requireActive();
        $collection = $this->collections->findDeclaredCollection(NewsCollectionDeclaration::OWNER, NewsCollectionDeclaration::KEY)
            ?? throw new NewsLegacyImportException('Synchronize the news collection declaration before importing legacy data.');
        return $this->relations->withCollectionLock($collection->id, function () use ($collection): NewsLegacyImportReportDTO {
            $warnings = [];
            $pending = [];
            foreach ($this->legacy->sections() as $row) {
                $pending[(int) $row['id']] = $row;
            }
            $sourceSections = count($pending);
            $sectionMap = [];
            $excluded = [];
            // Parents precede children. A missing parent or cycle aborts instead of losing a branch.
            while ($pending !== []) {
                $groups = [];
                $processed = false;
                foreach ($pending as $id => $row) {
                    $parent = $this->parent($row['parent']);
                    if ($parent !== null && ! isset($sectionMap[$parent]) && ! isset($excluded[$parent])) {
                        continue;
                    }
                    unset($pending[$id]);
                    $processed = true;
                    if ($row['deleted_at'] !== null || ($parent !== null && isset($excluded[$parent]))) {
                        $excluded[$id] = true;
                        continue;
                    }
                    $groups[$parent ?? 0][] = $row;
                }
                if (! $processed) {
                    throw new NewsLegacyImportException('Legacy section tree contains a missing parent or a cycle: ' . implode(', ', array_keys($pending)));
                }
                foreach ($groups as $oldParent => $rows) {
                    $parentId = $oldParent === 0 ? null : $sectionMap[$oldParent];
                    $path = $parentId === null ? [] : array_map(static fn ($section): string => $section->code, $this->sections->getPath($collection->id, $parentId));
                    $new = $this->importCodes(
                        $rows,
                        fn (string $code): ?int => $this->sections->resolvePath($collection->id, [...$path, $code])?->id,
                        function (array $row, string $code) use ($collection, $parentId, &$warnings): int {
                            $createdAt = $this->date($row['created_at'], 'section ' . $row['id'] . ' created_at', $warnings);
                            $updatedAt = $this->date($row['updated_at'], 'section ' . $row['id'] . ' updated_at', $warnings);
                            return $this->forRecord('section ' . $row['id'], fn (): int => $this->import->importSection(new CollectionSectionFormDTO(
                                $collection->id,
                                $parentId,
                                $code,
                                (string) $row['name'],
                                $this->text($row['text']),
                                true,
                                100,
                                ['meta_keywords' => $this->text($row['keywords']), 'meta_description' => $this->text($row['description'])]
                            ), $createdAt, $updatedAt));
                        },
                        $warnings,
                        'section'
                    );
                    $sectionMap += $new;
                }
            }
            $groups = [];
            $excludedArticles = 0;
            foreach ($this->legacy->articles() as $row) {
                $oldSection = $this->parent($row['section_id']);
                if ($oldSection !== null && ! isset($sectionMap[$oldSection]) && ! isset($excluded[$oldSection])) {
                    throw new NewsLegacyImportException('Article ' . $row['id'] . ' refers to a missing section.');
                }
                if ($row['deleted_at'] !== null || ($oldSection !== null && isset($excluded[$oldSection]))) {
                    ++$excludedArticles;
                    $this->legacy->queueFiles($this->fileIds($row['attached_files']));
                    continue;
                }
                $groups[$oldSection ?? 0][] = $row;
            }
            $articleMap = [];
            $expectedUrls = [];
            foreach ($groups as $oldSection => $rows) {
                $sectionId = $oldSection === 0 ? null : $sectionMap[$oldSection];
                $countBefore = $this->collections->countItems(new ContentCollectionItemQuery(collectionId: $collection->id, sectionId: $sectionId, onlyActive: false, withoutSection: $sectionId === null));
                $new = $this->importCodes(
                    $rows,
                    fn (string $code): ?int => $this->collections->getItemByCode($collection->id, $sectionId, $code)?->id,
                    function (array $row, string $code) use ($collection, $sectionId, &$warnings, &$expectedUrls): int {
                        $oldId = (int) $row['id'];
                        $from = $this->date($row['active_from'], "article $oldId active_from", $warnings);
                        $to = $this->date($row['active_to'], "article $oldId active_to", $warnings);
                        $active = (bool) $row['active'];
                        if ($from !== null && $to !== null && $from > $to) {
                            // 9.9 accepted such a window and never showed the article: it stays hidden.
                            $warnings[] = "Article $oldId: active_from {$row['active_from']} is later than active_to {$row['active_to']}; imported hidden without active_to.";
                            $active = false;
                            $to = null;
                        }
                        $createdAt = $this->date($row['created_at'], "article $oldId created_at", $warnings);
                        $updatedAt = $this->date($row['updated_at'], "article $oldId updated_at", $warnings);
                        $id = $this->forRecord("article $oldId", fn (): int => $this->import->importItem(new CollectionItemFormDTO(
                            $collection->id,
                            $sectionId,
                            $code,
                            (string) $row['name'],
                            $active,
                            $from?->format('Y-m-d H:i:s'),
                            $to?->format('Y-m-d H:i:s'),
                            100,
                            $this->text($row['preview_text']),
                            $this->text($row['text']),
                            ['page_title' => $this->text($row['page_title']) ?? '', 'description' => $this->text($row['description']) ?? '', 'keywords' => $this->text($row['keywords']) ?? '',
                                'tags' => $row['tags'] === null || $row['tags'] === '' ? [] : array_map(static fn (?string $tag): string => $tag ?? '', str_getcsv((string) $row['tags'], ',', '"', ''))]
                        ), new CollectionItemHistoryDTO(
                            $createdAt,
                            $updatedAt,
                            $row['created_by'] === null ? null : (int) $row['created_by'],
                            $row['updated_by'] === null ? null : (int) $row['updated_by'],
                            (int) ($row['view_count'] ?? 0)
                        )));
                        $expectedFiles = [];
                        foreach ($this->fileIds($row['attached_files']) as $fileId) {
                            if (! $this->files->isRegistered($fileId)) {
                                $warnings[] = "Article $oldId: file $fileId is missing from the file registry; no link imported.";
                                continue;
                            }
                            $expectedFiles[] = $fileId;
                            $owned = $this->files->filterIdsInDirectory([$fileId], 'news') !== [] || $this->files->filterIdsInDirectory([$fileId], 'news_comments') !== [];
                            $this->itemFiles->registerExisting($collection->id, $id, [$fileId], $owned && ! $this->discussion->referencesFile($fileId));
                        }
                        sort($expectedFiles);
                        if ($this->itemFiles->registeredFileIds($collection->id, $id) !== $expectedFiles) {
                            throw new NewsLegacyImportException("File link verification failed for article $oldId.");
                        }
                        $this->legacy->mapDiscussions($oldId, $id);
                        $item = $this->collections->getItem($id) ?? throw new NewsLegacyImportException("Imported article $oldId is missing.");
                        if ($item->code !== $code || $item->sectionId !== $sectionId || $this->collections->getItemByCode($collection->id, $sectionId, $code)?->id !== $id) {
                            throw new NewsLegacyImportException("Canonical lookup verification failed for article $oldId.");
                        }
                        $expectedUrls[$oldId] = $this->urls->itemUrl($collection, $item) ?? throw new NewsLegacyImportException("Canonical URL is missing for article $oldId.");
                        return $id;
                    },
                    $warnings,
                    'article'
                );
                if ($this->collections->countItems(new ContentCollectionItemQuery(collectionId: $collection->id, sectionId: $sectionId, onlyActive: false, withoutSection: $sectionId === null)) !== $countBefore + count($rows)) {
                    throw new NewsLegacyImportException('Imported article count does not match in section ' . $oldSection);
                }
                $articleMap += $new;
            }
            foreach ($articleMap as $oldId => $id) {
                $this->legacy->verifyDiscussions($oldId, $id);
            }
            return new NewsLegacyImportReportDTO($sectionMap, $articleMap, $expectedUrls, $warnings, $sourceSections - count($sectionMap), $excludedArticles);
        });
    }

    /**
     * Import all original codes before allocating replacements, so a generated suffix cannot
     * steal another record's original URL. Database lookup uses the actual column collation.
     * @param list<array<string, mixed>> $rows
     * @param callable(string): ?int $find
     * @param callable(array<string, mixed>, string): int $save
     * @param list<string> $warnings
     * @return array<int, int>
     */
    private function importCodes(array $rows, callable $find, callable $save, array &$warnings, string $kind): array
    {
        $map = [];
        $deferred = [];
        foreach ($rows as $row) {
            $code = (string) ($row['code'] ?? '');
            $existing = $code === '' ? null : $this->forRecord($kind . ' ' . $row['id'], static fn (): ?int => $find($code));
            if ($code === '' || ($existing !== null && in_array($existing, $map, true))) {
                $deferred[] = $row;
                continue;
            }
            if ($existing !== null) {
                throw new NewsLegacyImportException("Legacy $kind " . $row['id'] . " conflicts with existing collection content at code '$code'. Resolve the conflict before retrying system:update.");
            }
            $map[(int) $row['id']] = $save($row, $code);
        }
        foreach ($deferred as $row) {
            $old = (string) ($row['code'] ?? '');
            $base = $old !== '' ? $old : Str::slug((string) $row['name']);
            $base = $base !== '' ? $base : $kind;
            $code = mb_substr($base, 0, 255);
            $attempt = 0;
            while ($find($code) !== null) {
                $suffix = '-legacy-' . $row['id'] . ($attempt === 0 ? '' : '-' . $attempt);
                $code = mb_substr($base, 0, 255 - strlen($suffix)) . $suffix;
                ++$attempt;
            }
            $map[(int) $row['id']] = $save($row, $code);
            $warnings[] = ucfirst($kind) . ' ' . $row['id'] . ": code '$old' changed to '$code'.";
        }
        return $map;
    }

    /**
     * Validation names fields only, while the person repairing the data needs the legacy row.
     * @template T
     * @param callable(): T $write
     * @return T
     */
    private function forRecord(string $record, callable $write): mixed
    {
        try {
            return $write();
        } catch (CollectionItemFormException | CollectionSectionFormException $exception) {
            $details = [];
            foreach ($exception->errors as $field => $messages) {
                $details[] = $field . ': ' . implode(' ', $messages);
            }
            throw $this->unimportable($record, implode(' ', $details), $exception);
        } catch (\InvalidArgumentException $exception) {
            throw $this->unimportable($record, $exception->getMessage(), $exception);
        }
    }

    private function unimportable(string $record, string $reason, \Throwable $previous): NewsLegacyImportException
    {
        return new NewsLegacyImportException(
            message: sprintf('Legacy %s cannot be imported. %s Repair it and retry system:update.', $record, $reason),
            previous: $previous
        );
    }

    /** @param list<string> $warnings */
    private function date(mixed $value, string $label, array &$warnings): ?DateTimeImmutable
    {
        if ($value === null) {
            return null;
        }
        if ($value === '0000-00-00 00:00:00') {
            $warnings[] = "$label: legacy zero date changed to NULL.";
            return null;
        }
        if (! is_string($value)) {
            throw new NewsLegacyImportException("Invalid historical date in $label.");
        }
        $date = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $value, new DateTimeZone('UTC'));
        if ($date === false || $date->format('Y-m-d H:i:s') !== $value) {
            throw new NewsLegacyImportException("Invalid historical date in $label: $value.");
        }
        return $date;
    }

    private function parent(mixed $value): ?int
    {
        return $value === null || (int) $value === 0 ? null : (int) $value;
    }

    private function text(mixed $value): ?string
    {
        return $value === null ? null : (string) $value;
    }

    /** @return list<int> */
    private function fileIds(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }
        try {
            $ids = json_decode((string) $value, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new NewsLegacyImportException(message: 'Invalid JSON in legacy article attachments.', previous: $exception);
        }
        if (! is_array($ids) || ! array_is_list($ids)) {
            throw new NewsLegacyImportException('Legacy article attachments must be a list of file IDs.');
        }
        $result = [];
        foreach ($ids as $id) {
            if ((! is_int($id) && ! (is_string($id) && ctype_digit($id))) || (int) $id < 1) {
                throw new NewsLegacyImportException('Invalid file ID in legacy article attachments.');
            }
            $result[] = (int) $id;
        }
        return array_values(array_unique($result));
    }
}
