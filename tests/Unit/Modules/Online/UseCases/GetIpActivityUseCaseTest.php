<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Online\UseCases;

use Johncms\Modules\Online\Application\UseCases\GetIpActivityUseCase;
use Johncms\Http\Environment;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class GetIpActivityUseCaseTest extends TestCase
{
    private Environment&MockObject $env;

    protected function setUp(): void
    {
        $this->env = $this->createMock(Environment::class);
    }

    public function testCountReturnsNumberOfDistinctIps(): void
    {
        $this->env->method('getIpLog')->willReturn([100, 100, 200]);

        self::assertSame(2, $this->makeUseCase()->count());
    }

    public function testGetPageSortsByCountAndMarksCurrentIp(): void
    {
        $this->env->method('getIpLog')->willReturn([100, 100, 200]);
        $this->env->method('getIp')->willReturn(200);

        $items = $this->makeUseCase()->getPage(10, 0);

        self::assertCount(2, $items);

        // Самый частый IP идёт первым
        self::assertSame(long2ip(100), $items[0]['ip']);
        self::assertSame(2, $items[0]['count']);
        self::assertFalse($items[0]['current_user_ip']);
        self::assertSame('/admin/ip-search?ip=' . long2ip(100), $items[0]['search_ip']);
        self::assertSame('/admin/ip-whois?ip=' . long2ip(100), $items[0]['whois_ip']);

        self::assertSame(long2ip(200), $items[1]['ip']);
        self::assertSame(1, $items[1]['count']);
        self::assertTrue($items[1]['current_user_ip']);
    }

    public function testGetPageAppliesOffsetAndLimit(): void
    {
        $this->env->method('getIpLog')->willReturn([100, 100, 200, 300, 300, 300]);
        $this->env->method('getIp')->willReturn(0);

        $items = $this->makeUseCase()->getPage(1, 1);

        // Сортировка по убыванию: 300 (3), 100 (2), 200 (1); offset 1, limit 1 → 100
        self::assertCount(1, $items);
        self::assertSame(long2ip(100), $items[0]['ip']);
        self::assertSame(2, $items[0]['count']);
    }

    private function makeUseCase(): GetIpActivityUseCase
    {
        return new GetIpActivityUseCase($this->env);
    }
}
