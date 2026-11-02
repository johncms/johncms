<?php

declare(strict_types=1);

namespace Tests\Support;

use Gettext\Translator;
use Gettext\TranslatorFunctions;
use Johncms\Modules\Collections\Application\Services\CollectionItemValidator;
use Johncms\Modules\Collections\Application\UseCases\SaveCollectionItemUseCase;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionItemValueRepositoryInterface;
use Johncms\Modules\Collections\Infrastructure\Persistence\CollectionTransaction;
use Johncms\Modules\Collections\Infrastructure\Persistence\Query\ContentCollectionItemQueryCompiler;
use Johncms\Modules\Collections\Infrastructure\Persistence\Repository\ContentCollectionFieldRepository;
use Johncms\Modules\Collections\Infrastructure\Persistence\Repository\ContentCollectionItemRepository;
use Johncms\Modules\Collections\Infrastructure\Persistence\Repository\ContentCollectionItemValueRepository;
use Johncms\Modules\Collections\Infrastructure\Persistence\Repository\ContentCollectionRepository;
use Johncms\Modules\Collections\Infrastructure\Persistence\Repository\ContentCollectionSectionRepository;
use Johncms\Validator\RuleCompiler;
use Johncms\Validator\RuleFactories\PatternRuleFactory;
use Johncms\Validator\RuleFactories\StringLengthRuleFactory;
use Johncms\Validator\SymfonyValidator;
use Johncms\Validator\Translation\GettextTranslator;
use Symfony\Component\Validator\Validation;

final class CollectionItemWriterFactory
{
    public static function create(?ContentCollectionItemValueRepositoryInterface $values = null, ?\Johncms\Updates\SystemOperationContext $context = null, ?\Johncms\Modules\Collections\Application\Services\CollectionChangeDispatcher $changes = null): SaveCollectionItemUseCase
    {
        $validator = self::validator();
        $fields = new ContentCollectionFieldRepository();
        return new SaveCollectionItemUseCase(
            new ContentCollectionItemRepository($fields, new ContentCollectionItemQueryCompiler()), $fields,
            $values ?? new ContentCollectionItemValueRepository(), new ContentCollectionRepository(),
            new ContentCollectionSectionRepository(), new CollectionTransaction(), new CollectionItemValidator($validator), $context ?? new \Johncms\Updates\SystemOperationContext(), $changes ?? new \Johncms\Modules\Collections\Application\Services\CollectionChangeDispatcher()
        );
    }    public static function validator(): \Johncms\Validator\ValidatorInterface
    {
        TranslatorFunctions::register(new Translator());
        return new SymfonyValidator(
            Validation::createValidatorBuilder()->setTranslator(new GettextTranslator())->getValidator(),
            new RuleCompiler([new StringLengthRuleFactory(), new PatternRuleFactory()])
        );
    }
    public static function api(?\Johncms\Modules\Collections\Application\Services\CollectionChangeDispatcher $changes = null): \Johncms\Modules\Collections\Application\Api\CollectionsApi
    {
        $fields = new ContentCollectionFieldRepository();
        $items = new ContentCollectionItemRepository($fields, new ContentCollectionItemQueryCompiler());
        $collections = new ContentCollectionRepository();
        $writer = self::create(changes: $changes);
        return new \Johncms\Modules\Collections\Application\Api\CollectionsApi(
            $collections, $items, $writer,
            self::itemDeleter($changes),
            new \Johncms\Modules\Collections\Application\UseCases\PatchCollectionItemUseCase($items, $collections, $fields, new CollectionTransaction(), $writer),
            new \Johncms\Modules\Collections\Application\Services\CollectionApiMapper(),
            self::deletionPlanner(), self::collectionDeleter(changes: $changes)
        );
    }

    public static function sectionWriter(?\Johncms\Updates\SystemOperationContext $context = null, ?\Johncms\Modules\Collections\Application\Services\CollectionChangeDispatcher $changes = null): \Johncms\Modules\Collections\Application\UseCases\SaveCollectionSectionUseCase
    {
        return new \Johncms\Modules\Collections\Application\UseCases\SaveCollectionSectionUseCase(
            new ContentCollectionSectionRepository(), new ContentCollectionRepository(), new CollectionTransaction(),
            self::validator(), $context ?? new \Johncms\Updates\SystemOperationContext(), $changes ?? new \Johncms\Modules\Collections\Application\Services\CollectionChangeDispatcher()
        );
    }

    public static function sectionsApi(?\Johncms\Modules\Collections\Application\Services\CollectionChangeDispatcher $changes = null): \Johncms\Modules\Collections\Application\Api\CollectionSectionsApi
    {
        $sections = new ContentCollectionSectionRepository();
        return new \Johncms\Modules\Collections\Application\Api\CollectionSectionsApi(
            $sections, new \Johncms\Modules\Collections\Application\UseCases\ListCollectionSectionsUseCase($sections),
            self::sectionWriter(changes: $changes),
            new \Johncms\Modules\Collections\Application\UseCases\DeleteCollectionSectionUseCase($sections, new ContentCollectionItemRepository(new ContentCollectionFieldRepository(), new ContentCollectionItemQueryCompiler()), new CollectionTransaction(), self::deletionPlanner(), self::itemDeleter($changes), $changes ?? new \Johncms\Modules\Collections\Application\Services\CollectionChangeDispatcher()),
            new \Johncms\Modules\Collections\Application\Services\CollectionApiMapper(), self::deletionPlanner()
        );
    }

    public static function deletionPlanner(): \Johncms\Modules\Collections\Application\Services\CollectionDeletionPlanner
    {
        return new \Johncms\Modules\Collections\Application\Services\CollectionDeletionPlanner(
            new ContentCollectionRepository(), new ContentCollectionSectionRepository(),
            new ContentCollectionItemRepository(new ContentCollectionFieldRepository(), new ContentCollectionItemQueryCompiler()),
            new ContentCollectionFieldRepository()
        );
    }

    public static function itemDeleter(?\Johncms\Modules\Collections\Application\Services\CollectionChangeDispatcher $changes = null): \Johncms\Modules\Collections\Application\UseCases\DeleteCollectionItemUseCase
    {
        return new \Johncms\Modules\Collections\Application\UseCases\DeleteCollectionItemUseCase(
            new ContentCollectionItemRepository(new ContentCollectionFieldRepository(), new ContentCollectionItemQueryCompiler()),
            new ContentCollectionItemValueRepository(), new ContentCollectionRepository(), new CollectionTransaction(), $changes ?? new \Johncms\Modules\Collections\Application\Services\CollectionChangeDispatcher()
        );
    }

    public static function collectionDeleter(?\Johncms\Modules\Collections\Application\Services\CollectionCodeCacheInterface $cache = null, ?\Johncms\Modules\Collections\Application\Services\CollectionChangeDispatcher $changes = null): \Johncms\Modules\Collections\Application\UseCases\DeleteCollectionUseCase
    {
        $collections = new ContentCollectionRepository();
        return new \Johncms\Modules\Collections\Application\UseCases\DeleteCollectionUseCase(
            $collections, $cache ?? new \Johncms\Modules\Collections\Application\Services\CollectionCodeCache(InMemoryCache::create(), $collections),
            new CollectionTransaction(), self::deletionPlanner(), self::itemDeleter($changes),
            new ContentCollectionSectionRepository(), new ContentCollectionFieldRepository(), $changes ?? new \Johncms\Modules\Collections\Application\Services\CollectionChangeDispatcher()
        );
    }

    /** A synchronous coordinator for repository-only delegation tests. */
    public static function immediateTransaction(): \Johncms\Modules\Collections\Domain\Repository\CollectionTransactionInterface
    {
        return new class implements \Johncms\Modules\Collections\Domain\Repository\CollectionTransactionInterface {
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
