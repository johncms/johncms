<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Modules\Collections\Application\Api\CollectionItemRelationsApi;
use Johncms\Modules\Collections\Application\Exceptions\CollectionItemFormException;
use Johncms\Modules\Collections\Domain\Exceptions\CollectionWriteConflictException;
use Johncms\Modules\Collections\Infrastructure\Persistence\CollectionTransaction;
use Johncms\Modules\Collections\Infrastructure\Persistence\Query\ContentCollectionItemQueryCompiler;
use Johncms\Modules\Collections\Infrastructure\Persistence\Repository\ContentCollectionFieldRepository;
use Johncms\Modules\Collections\Infrastructure\Persistence\Repository\ContentCollectionItemRepository;
use Johncms\Modules\Collections\Infrastructure\Persistence\Repository\ContentCollectionRepository;
use Johncms\Modules\News\Infrastructure\Persistence\Repository\EloquentNewsDiscussionRepository;
use Tests\Support\CollectionItemWriterFactory;
use Tests\Support\CollectionWriterDatabase;

CollectionWriterDatabase::boot();
CollectionItemWriterFactory::validator();
echo Capsule::connection()->selectOne('SELECT CONNECTION_ID() AS id')->id . PHP_EOL;
flush();
$collection = (int) $argv[1];
$item = (int) $argv[2];
$mode = $argv[3];
$relations = new CollectionItemRelationsApi(new CollectionTransaction(), new ContentCollectionRepository(), new ContentCollectionItemRepository(new ContentCollectionFieldRepository(), new ContentCollectionItemQueryCompiler()));
$discussion = new EloquentNewsDiscussionRepository();
try {
    $relations->withItemLock(collectionId: $collection, itemId: $item, operation: static function () use ($mode, $item, $collection, $discussion): void {
        match ($mode) {
            'comment' => $discussion->addComment($item, 11, 'Comment', [], [321]),
            'vote' => $discussion->vote($item, 11, 1),
            'hide-item' => Capsule::table('collection_items')->where('id', $item)->update(['active' => false]),
            'hide-section' => Capsule::table('collection_sections')->where('collection_id', $collection)->update(['active' => false]),
            'hide-collection' => Capsule::table('collections')->where('id', $collection)->update(['active' => false]),
            default => throw new LogicException('Unknown relations test mode.'),
        };
    }, publishedOnly: true);
    exit(0);
} catch (CollectionItemFormException | CollectionWriteConflictException) {
    exit(2);
}
