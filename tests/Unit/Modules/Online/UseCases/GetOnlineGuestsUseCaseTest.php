<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Online\UseCases;

use Gettext\Translator;
use Gettext\TranslatorFunctions;
use Illuminate\Database\Eloquent\Collection;
use Johncms\Modules\Online\Application\DTO\OnlineItemDTO;
use Johncms\Modules\Online\Application\UseCases\GetOnlineGuestsUseCase;
use Johncms\Modules\Online\Domain\Repository\OnlineGuestRepositoryInterface;
use Johncms\System\Legacy\Tools;
use Johncms\Users\GuestSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class GetOnlineGuestsUseCaseTest extends TestCase
{
    private OnlineGuestRepositoryInterface&MockObject $repository;
    private Tools&MockObject $tools;

    protected function setUp(): void
    {
        // __('Guest') нуждается в зарегистрированном переводчике (возвращает оригиналы)
        TranslatorFunctions::register(new Translator());
        $this->repository = $this->createMock(OnlineGuestRepositoryInterface::class);
        $this->tools = $this->createMock(Tools::class);
    }

    public function testCountDelegatesToRepository(): void
    {
        $this->repository->expects(self::once())->method('countOnline')->willReturn(4);

        self::assertSame(4, $this->makeUseCase()->count());
    }

    public function testGetPageAnonymizesAndEnrichesGuests(): void
    {
        $guest = new GuestSession([
            'place'        => '/some/place',
            'movings'      => 2,
            'sestime'      => 50,
            'browser'      => 'UA',
            'ip'           => '1.2.3.4',
            'ip_via_proxy' => '0.0.0.0',
        ]);

        $this->repository->method('getOnline')->willReturn(new Collection([$guest]));
        $this->tools->method('displayPlace')->willReturnArgument(0);
        $this->tools->method('timecount')->willReturn('TC');

        $result = $this->makeUseCase()->getPage(10, 0, null);

        self::assertInstanceOf(OnlineItemDTO::class, $result[0]);
        self::assertSame(0, $result[0]->id);
        self::assertSame('Guest', $result[0]->name);
        self::assertSame('', $result[0]->profileUrl);
        self::assertSame('/some/place', $result[0]->placeName);
        self::assertSame('2 - TC', $result[0]->displayDate);
    }

    private function makeUseCase(): GetOnlineGuestsUseCase
    {
        return new GetOnlineGuestsUseCase($this->repository, $this->tools);
    }
}
