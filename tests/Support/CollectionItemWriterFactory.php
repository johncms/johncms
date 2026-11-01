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
    public static function create(?ContentCollectionItemValueRepositoryInterface $values = null, ?\Johncms\Updates\SystemOperationContext $context = null): SaveCollectionItemUseCase
    {
        $validator = self::validator();
        $fields = new ContentCollectionFieldRepository();
        return new SaveCollectionItemUseCase(
            new ContentCollectionItemRepository($fields, new ContentCollectionItemQueryCompiler()), $fields,
            $values ?? new ContentCollectionItemValueRepository(), new ContentCollectionRepository(),
            new ContentCollectionSectionRepository(), new CollectionTransaction(), new CollectionItemValidator($validator), $context ?? new \Johncms\Updates\SystemOperationContext()
        );
    }    public static function validator(): \Johncms\Validator\ValidatorInterface
    {
        TranslatorFunctions::register(new Translator());
        return new SymfonyValidator(
            Validation::createValidatorBuilder()->setTranslator(new GettextTranslator())->getValidator(),
            new RuleCompiler([new StringLengthRuleFactory(), new PatternRuleFactory()])
        );
    }
    public static function api(): \Johncms\Modules\Collections\Application\Api\CollectionsApi
    {
        $fields = new ContentCollectionFieldRepository();
        $items = new ContentCollectionItemRepository($fields, new ContentCollectionItemQueryCompiler());
        $collections = new ContentCollectionRepository();
        $writer = self::create();
        return new \Johncms\Modules\Collections\Application\Api\CollectionsApi(
            $collections, $items, $writer,
            new \Johncms\Modules\Collections\Application\UseCases\DeleteCollectionItemUseCase($items),
            new \Johncms\Modules\Collections\Application\UseCases\PatchCollectionItemUseCase($items, $collections, $fields, new CollectionTransaction(), $writer),
            new \Johncms\Modules\Collections\Application\Services\CollectionApiMapper()
        );
    }

}
