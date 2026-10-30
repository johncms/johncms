<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Admin\UseCases;

use Johncms\Modules\Admin\Application\UseCases\SaveLanguageSettingsUseCase;
use Johncms\Modules\Admin\Domain\Repository\SystemConfigRepositoryInterface;
use Johncms\Modules\Admin\Domain\Services\LanguageFilesManagerInterface;
use PHPUnit\Framework\TestCase;

final class SaveLanguageSettingsUseCaseTest extends TestCase
{
    private const array INSTALLED = [
        'en' => ['name' => 'English', 'version' => 1.0],
        'ru' => ['name' => 'Русский', 'version' => 1.0],
        'uk' => ['name' => 'Українська', 'version' => 1.0],
    ];

    public function testTheListHoldsTheLanguagesTurnedOnAndTheDefault(): void
    {
        $saved = $this->save(['lng' => 'en'], 'ru', ['uk']);

        self::assertSame('ru', $saved['lng']);
        self::assertSame(['ru', 'uk'], array_keys($saved['lng_list']));
        self::assertSame('Українська', $saved['lng_list']['uk']['name']);
    }

    public function testALanguageWithoutFilesIsNeitherTheDefaultNorOnOffer(): void
    {
        $saved = $this->save(['lng' => 'en'], 'de', ['de', 'ru']);

        self::assertSame('en', $saved['lng']);
        self::assertSame(['en', 'ru'], array_keys($saved['lng_list']));
    }

    /**
     * @param array<string, mixed> $config
     * @param list<string>         $enabled
     * @return array<string, mixed> What the use case wrote.
     */
    private function save(array $config, string $default, array $enabled): array
    {
        $repository = new class ($config) implements SystemConfigRepositoryInterface {
            /** @var array<string, mixed> */
            public array $saved = [];

            /**
             * @param array<string, mixed> $config
             */
            public function __construct(private readonly array $config)
            {
            }

            public function getJohncms(): array
            {
                return $this->config;
            }

            public function saveJohncms(array $johncms): void
            {
                $this->saved = $johncms;
            }
        };

        $files = new class implements LanguageFilesManagerInterface {
            public function getInstalled(): array
            {
                return SaveLanguageSettingsUseCaseTest::installed();
            }
        };

        (new SaveLanguageSettingsUseCase($repository, $files))->execute($default, $enabled);

        return $repository->saved;
    }

    /**
     * @return array<string, array{name: string, version: float}>
     */
    public static function installed(): array
    {
        return self::INSTALLED;
    }
}
