<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\Collections;

use Johncms\Database\ConnectionInterface;
use Johncms\Modules\Collections\Application\Api\CollectionLookupApiInterface;
use Johncms\Modules\Collections\Application\Services\CollectionSearchIndexer;
use Johncms\Modules\News\Domain\Repository\NewsLegacyStorageRepositoryInterface;
use Johncms\Updates\SystemOperationContext;
use Psr\Log\LoggerInterface;

final readonly class NewsStorageUpdater
{
    public function __construct(
        private NewsLegacyImporter $importer,
        private NewsLegacyStorageRepositoryInterface $legacy,
        private ConnectionInterface $db,
        private NewsDiscussionFiles $files,
        private CollectionLookupApiInterface $lookup,
        private CollectionSearchIndexer $search,
        private SystemOperationContext $context,
        private LoggerInterface $logger,
    ) {
    }

    public function update(): void
    {
        $this->context->requireActive();
        if (! $this->legacy->hasLegacySchema() && $this->legacy->pendingFiles() === []) {
            return;
        }
        $message = 'Updating news storage. Back up the database and uploads before system:update; DDL cleanup cannot be rolled back.';
        $this->logger->info($message);
        $this->context->report($message);
        $this->legacy->prepare();
        if ($this->legacy->hasData()) {
            $report = $this->db->transaction(function () {
                $report = $this->importer->import();
                $this->legacy->clearLegacyRows();
                return $report;
            });
            foreach ($report->lines() as $line) {
                $this->logger->info($line);
                $this->context->report($line);
            }
        }
        $this->legacy->removeOrphanDiscussions();
        $collection = $this->lookup->findDeclared(NewsCollectionDeclaration::OWNER, NewsCollectionDeclaration::KEY)
            ?? throw new \LogicException('Synchronize the news declaration before updating storage.');
        while (($ids = $this->legacy->pendingFiles()) !== []) {
            foreach ($ids as $id) {
                $this->files->cleanup([$id], $collection->id);
                $this->legacy->acknowledgeFile($id);
            }
        }
        $this->search->rebuildCollection($collection->id);
        $this->legacy->dropLegacySchema();
    }
}
