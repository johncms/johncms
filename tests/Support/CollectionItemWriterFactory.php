<?php

declare(strict_types=1);

namespace Tests\Support;

use Gettext\Translator;
use Gettext\TranslatorFunctions;
use Johncms\Modules\Collections\Application\Api\CollectionSectionsApi;
use Johncms\Modules\Collections\Application\Api\CollectionsApi;
use Johncms\Modules\Collections\Application\Services\CollectionApiMapper;
use Johncms\Modules\Collections\Application\Services\CollectionStructureMemo;
use Johncms\Modules\Collections\Application\Services\CollectionChangeDispatcher;
use Johncms\Modules\Collections\Application\Services\CollectionCodeCache;
use Johncms\Modules\Collections\Application\Services\CollectionCodeCacheInterface;
use Johncms\Modules\Collections\Application\Services\CollectionDeletionPlanner;
use Johncms\Modules\Collections\Application\Services\CollectionFileValues;
use Johncms\Modules\Collections\Application\Services\CollectionItemFiles;
use Johncms\Modules\Collections\Application\Services\CollectionItemValidator;
use Johncms\Modules\Collections\Application\Services\CollectionRelationDeletion;
use Johncms\Modules\Collections\Application\UseCases\DeleteCollectionItemUseCase;
use Johncms\Modules\Collections\Application\UseCases\DeleteCollectionSectionUseCase;
use Johncms\Modules\Collections\Application\UseCases\DeleteCollectionUseCase;
use Johncms\Modules\Collections\Application\UseCases\ListCollectionSectionsUseCase;
use Johncms\Modules\Collections\Application\UseCases\PatchCollectionItemUseCase;
use Johncms\Modules\Collections\Application\UseCases\SaveCollectionItemUseCase;
use Johncms\Modules\Collections\Application\UseCases\SaveCollectionSectionUseCase;
use Johncms\Modules\Collections\Domain\Repository\CollectionTransactionInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionItemValueRepositoryInterface;
use Johncms\Modules\Collections\Infrastructure\Persistence\CollectionTransaction;
use Johncms\Modules\Collections\Infrastructure\Persistence\Query\ContentCollectionItemQueryCompiler;
use Johncms\Modules\Collections\Infrastructure\Persistence\Repository\ContentCollectionFieldRepository;
use Johncms\Modules\Collections\Infrastructure\Persistence\Repository\ContentCollectionItemRepository;
use Johncms\Modules\Collections\Infrastructure\Persistence\Repository\ContentCollectionItemValueRepository;
use Johncms\Modules\Collections\Infrastructure\Persistence\Repository\ContentCollectionRepository;
use Johncms\Modules\Collections\Infrastructure\Persistence\Repository\ContentCollectionSectionRepository;
use Johncms\Updates\SystemOperationContext;
use Johncms\Validator\RuleCompiler;
use Johncms\Validator\RuleFactories\PatternRuleFactory;
use Johncms\Validator\RuleFactories\StringLengthRuleFactory;
use Johncms\Validator\SymfonyValidator;
use Johncms\Validator\Translation\GettextTranslator;
use Johncms\Validator\ValidatorInterface;
use Symfony\Component\Validator\Validation;
use Johncms\System\i18n\Translator as CmsTranslator;

final class CollectionItemWriterFactory
{
    public static function itemRepository(): ContentCollectionItemRepository
    {
        return new ContentCollectionItemRepository(new ContentCollectionFieldRepository(), new ContentCollectionItemQueryCompiler());
    }

    public static function create(?ContentCollectionItemValueRepositoryInterface $values = null, ?SystemOperationContext $context = null, ?CollectionChangeDispatcher $changes = null, ?CollectionFileValues $fileValues = null, ?CollectionItemFiles $files = null): SaveCollectionItemUseCase
    {
        $validator = self::validator();
        $fields = new ContentCollectionFieldRepository();
        return new SaveCollectionItemUseCase(
            itemRepository: new ContentCollectionItemRepository($fields, new ContentCollectionItemQueryCompiler()), fieldRepository: $fields,
            valueRepository: $values ?? new ContentCollectionItemValueRepository(), collections: new ContentCollectionRepository(),
            sections: new ContentCollectionSectionRepository(), transaction: new CollectionTransaction(), validator: new CollectionItemValidator($validator), systemContext: $context ?? new SystemOperationContext(), changes: $changes ?? new CollectionChangeDispatcher(), files: $files, fileValues: $fileValues
        );
    }    public static function validator(): ValidatorInterface
    {
        TranslatorFunctions::register(new Translator());
        return new SymfonyValidator(
            Validation::createValidatorBuilder()->setTranslator(new GettextTranslator())->getValidator(),
            new RuleCompiler([new StringLengthRuleFactory(), new PatternRuleFactory()])
        );
    }
    public static function api(?CollectionChangeDispatcher $changes = null): CollectionsApi
    {
        $fields = new ContentCollectionFieldRepository();
        $items = new ContentCollectionItemRepository($fields, new ContentCollectionItemQueryCompiler());
        $collections = new ContentCollectionRepository();
        $writer = self::create(changes: $changes);
        return new CollectionsApi(
            $collections, $items, $writer,
            self::itemDeleter($changes),
            new PatchCollectionItemUseCase($items, $collections, $fields, new CollectionTransaction(), $writer),
            new CollectionApiMapper(),
            self::deletionPlanner(), self::collectionDeleter(changes: $changes)
        );
    }

    public static function sectionWriter(?SystemOperationContext $context = null, ?CollectionChangeDispatcher $changes = null): SaveCollectionSectionUseCase
    {
        return new SaveCollectionSectionUseCase(
            new ContentCollectionSectionRepository(), new ContentCollectionRepository(), new CollectionTransaction(),
            self::validator(), $context ?? new SystemOperationContext(), $changes ?? new CollectionChangeDispatcher()
        );
    }

    public static function sectionsApi(?CollectionChangeDispatcher $changes = null): CollectionSectionsApi
    {
        $sections = new ContentCollectionSectionRepository();
        return new CollectionSectionsApi(
            $sections, new ListCollectionSectionsUseCase($sections),
            self::sectionWriter(changes: $changes),
            new DeleteCollectionSectionUseCase($sections, new ContentCollectionItemRepository(new ContentCollectionFieldRepository(), new ContentCollectionItemQueryCompiler()), new CollectionTransaction(), self::deletionPlanner(), self::itemDeleter($changes), $changes ?? new CollectionChangeDispatcher()),
            new CollectionApiMapper(), self::deletionPlanner(), new CollectionStructureMemo(InMemoryCache::create(), new CmsTranslator())
        );
    }

    public static function relationDeletion(): CollectionRelationDeletion
    {
        return new CollectionRelationDeletion(
            new ContentCollectionItemValueRepository(), new ContentCollectionFieldRepository(), self::itemRepository(), new ContentCollectionRepository()
        );
    }

    public static function deletionPlanner(): CollectionDeletionPlanner
    {
        return new CollectionDeletionPlanner(
            new ContentCollectionRepository(), new ContentCollectionSectionRepository(),
            new ContentCollectionItemRepository(new ContentCollectionFieldRepository(), new ContentCollectionItemQueryCompiler()),
            new ContentCollectionFieldRepository(), self::relationDeletion()
        );
    }

    public static function itemDeleter(?CollectionChangeDispatcher $changes = null): DeleteCollectionItemUseCase
    {
        return new DeleteCollectionItemUseCase(
            new ContentCollectionItemRepository(new ContentCollectionFieldRepository(), new ContentCollectionItemQueryCompiler()),
            new ContentCollectionItemValueRepository(), new ContentCollectionRepository(), new CollectionTransaction(), self::relationDeletion(), $changes ?? new CollectionChangeDispatcher()
        );
    }

    public static function collectionDeleter(?CollectionCodeCacheInterface $cache = null, ?CollectionChangeDispatcher $changes = null): DeleteCollectionUseCase
    {
        $collections = new ContentCollectionRepository();
        return new DeleteCollectionUseCase(
            $collections, $cache ?? new CollectionCodeCache(InMemoryCache::create(), $collections),
            new CollectionTransaction(), self::deletionPlanner(), self::itemDeleter($changes),
            new ContentCollectionSectionRepository(), new ContentCollectionFieldRepository(), $changes ?? new CollectionChangeDispatcher()
        );
    }

    /** A synchronous coordinator for repository-only delegation tests. */
    public static function immediateTransaction(): CollectionTransactionInterface
    {
        return new class implements CollectionTransactionInterface {
            public function run(callable $operation): mixed
            {
                return $operation();
            }

            public function afterCommit(callable $callback): void
            {
                $callback();
            }
        };
    }

}
