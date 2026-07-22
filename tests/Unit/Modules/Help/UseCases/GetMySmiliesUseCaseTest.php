<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Help\UseCases;

use Johncms\Modules\Help\Application\UseCases\GetMySmiliesUseCase;
use Johncms\Smilies\SmiliesRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tests\Support\UserFactory;

final class GetMySmiliesUseCaseTest extends TestCase
{
    private SmiliesRendererInterface&MockObject $smiliesRenderer;

    protected function setUp(): void
    {
        $this->smiliesRenderer = $this->createMock(SmiliesRendererInterface::class);
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
        $this->smiliesRenderer->method('render')->willReturn('<img>');

        $useCase = $this->makeUseCase(['alpha', 'beta', 'gamma']);

        $items = $useCase->getPage(2, 1);

        self::assertCount(2, $items);
        self::assertTrue($items[0]['can_del']);
        self::assertSame('beta', $items[0]['lat_smile']);
        self::assertSame(':бета:', $items[0]['smile']);
        self::assertSame('<img>', $items[0]['picture']);
        self::assertSame('gamma', $items[1]['lat_smile']);
    }

    private function makeUseCase(?array $smilies): GetMySmiliesUseCase
    {
        $user = UserFactory::make(attributes: ['smileys' => $smilies]);

        return new GetMySmiliesUseCase($user, $this->smiliesRenderer);
    }
}
