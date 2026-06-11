<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Help\UseCases;

use Johncms\Modules\Help\Application\UseCases\GetMySmiliesUseCase;
use Johncms\System\Legacy\Tools;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tests\Support\UserFactory;

final class GetMySmiliesUseCaseTest extends TestCase
{
    private Tools&MockObject $tools;

    protected function setUp(): void
    {
        $this->tools = $this->createMock(Tools::class);
    }

    public function testCountReturnsNumberOfUserSmilies(): void
    {
        $useCase = $this->makeUseCase(['alpha', 'beta', 'gamma']);

        self::assertSame(3, $useCase->count());
    }

    public function testCountIsZeroWhenSmiliesAreNotAnArray(): void
    {
        $useCase = $this->makeUseCase(null);

        self::assertSame(0, $useCase->count());
    }

    public function testGetPageSlicesAndBuildsItems(): void
    {
        $this->tools->method('trans')->willReturnArgument(0);
        $this->tools->method('smilies')->willReturn('<img>');

        $useCase = $this->makeUseCase(['alpha', 'beta', 'gamma']);

        $items = $useCase->getPage(2, 1);

        self::assertCount(2, $items);
        self::assertTrue($items[0]['can_del']);
        self::assertSame('beta', $items[0]['lat_smile']);
        self::assertSame(':beta:', $items[0]['smile']);
        self::assertSame('<img>', $items[0]['picture']);
        self::assertSame('gamma', $items[1]['lat_smile']);
    }

    private function makeUseCase(?array $smilies): GetMySmiliesUseCase
    {
        $user = UserFactory::make(attributes: ['smileys' => $smilies]);

        return new GetMySmiliesUseCase($user, $this->tools);
    }
}
