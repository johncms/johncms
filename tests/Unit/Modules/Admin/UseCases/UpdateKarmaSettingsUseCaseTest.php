<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Admin\UseCases;

use Johncms\Modules\Admin\Application\DTO\KarmaSettingsDTO;
use Johncms\Modules\Admin\Application\UseCases\UpdateKarmaSettingsUseCase;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeSystemConfigRepository;

/**
 * What the karma settings write into the configuration: the four values of the form, and
 * nothing else of the section or of the site is lost on the way.
 */
final class UpdateKarmaSettingsUseCaseTest extends TestCase
{
    public function testTheValuesOfTheFormAreWrittenIntoTheSection(): void
    {
        $saved = $this->save(new KarmaSettingsDTO(karmaPoints: 7, forumPosts: 25, enabled: true, staffVotable: true));

        self::assertSame(7, $saved['karma']['karma_points']);
        self::assertSame(25, $saved['karma']['forum']);
        self::assertSame(1, $saved['karma']['on']);
        self::assertSame(1, $saved['karma']['adm']);
    }

    public function testSwitchedOffSettingsAreWrittenAsZero(): void
    {
        $saved = $this->save(new KarmaSettingsDTO(karmaPoints: 5, forumPosts: 10, enabled: false, staffVotable: false));

        self::assertSame(0, $saved['karma']['on']);
        self::assertSame(0, $saved['karma']['adm']);
    }

    public function testTheRestOfTheSectionAndOfTheSiteIsKept(): void
    {
        $saved = $this->save(
            new KarmaSettingsDTO(karmaPoints: 5, forumPosts: 10, enabled: true, staffVotable: false),
            ['copyright' => 'JohnCMS', 'karma' => ['karma_time' => 86400, 'time' => 0]]
        );

        self::assertSame('JohnCMS', $saved['copyright']);
        self::assertSame(86400, $saved['karma']['karma_time']);
        self::assertSame(0, $saved['karma']['time']);
    }

    /**
     * @param array<string, mixed> $existing
     * @return array<string, mixed>
     */
    private function save(KarmaSettingsDTO $dto, array $existing = []): array
    {
        $configRepository = new FakeSystemConfigRepository($existing);
        (new UpdateKarmaSettingsUseCase($configRepository))->execute($dto);

        return $configRepository->saved;
    }
}
