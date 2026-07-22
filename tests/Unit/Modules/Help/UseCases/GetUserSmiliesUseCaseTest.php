<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Help\UseCases;

use Gettext\Translator;
use Gettext\TranslatorFunctions;
use Johncms\Modules\Help\Application\UseCases\GetUserSmiliesUseCase;
use PHPUnit\Framework\TestCase;
use Tests\Support\UserFactory;

final class GetUserSmiliesUseCaseTest extends TestCase
{
    protected function setUp(): void
    {
        // categoryTitle для известных категорий дёргает __() — нужен зарегистрированный переводчик
        TranslatorFunctions::register(new Translator());
    }

    public function testCategoryTitleReturnsTranslatedNameForKnownCategory(): void
    {
        self::assertSame('Animals', $this->makeUseCase()->categoryTitle('animals'));
    }

    public function testCategoryTitleFallsBackToCapitalizedRawValue(): void
    {
        self::assertSame('Unknown', $this->makeUseCase()->categoryTitle('unknown'));
    }

    private function makeUseCase(): GetUserSmiliesUseCase
    {
        return new GetUserSmiliesUseCase(UserFactory::make());
    }
}
