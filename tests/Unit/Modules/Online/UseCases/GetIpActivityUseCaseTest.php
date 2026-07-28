<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Online\UseCases;

use Johncms\Modules\Online\Application\UseCases\GetIpActivityUseCase;
use Johncms\Security\RequestRateLogInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class GetIpActivityUseCaseTest extends TestCase
{
    private RequestRateLogInterface&MockObject $requestRateLog;

    protected function setUp(): void
    {
        $this->requestRateLog = $this->createMock(RequestRateLogInterface::class);
    }

    public function testCountReturnsNumberOfDistinctIps(): void
    {
        $this->requestRateLog->method('recentAddresses')->willReturn(['10.0.0.1', '10.0.0.1', '10.0.0.2']);

        self::assertSame(2, $this->makeUseCase()->count());
    }

    public function testGetPageSortsByCountAndMarksCurrentIp(): void
    {
        $this->requestRateLog->method('recentAddresses')->willReturn(['10.0.0.1', '10.0.0.1', '10.0.0.2']);

        $items = $this->makeUseCase()->getPage(10, 0, '10.0.0.2');

        self::assertCount(2, $items);

        // Самый частый IP идёт первым
        self::assertSame('10.0.0.1', $items[0]['ip']);
        self::assertSame(2, $items[0]['count']);
        self::assertFalse($items[0]['current_user_ip']);
        self::assertSame('/admin/ip-search?ip=10.0.0.1', $items[0]['search_ip']);
        self::assertSame('/admin/ip-whois?ip=10.0.0.1', $items[0]['whois_ip']);

        self::assertSame('10.0.0.2', $items[1]['ip']);
        self::assertSame(1, $items[1]['count']);
        self::assertTrue($items[1]['current_user_ip']);
    }

    public function testGetPageAppliesOffsetAndLimit(): void
    {
        $this->requestRateLog->method('recentAddresses')->willReturn(
            ['10.0.0.1', '10.0.0.1', '10.0.0.2', '10.0.0.3', '10.0.0.3', '10.0.0.3']
        );

        $items = $this->makeUseCase()->getPage(1, 1, '');

        // Сортировка по убыванию: 10.0.0.3 (3), 10.0.0.1 (2), 10.0.0.2 (1); offset 1, limit 1
        self::assertCount(1, $items);
        self::assertSame('10.0.0.1', $items[0]['ip']);
        self::assertSame(2, $items[0]['count']);
    }

    private function makeUseCase(): GetIpActivityUseCase
    {
        return new GetIpActivityUseCase($this->requestRateLog);
    }
}
