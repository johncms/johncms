<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Collections\Application;

use Johncms\Modules\Collections\Application\Services\ItemListFilterMapper;
use Johncms\Modules\Collections\Domain\Enums\ItemStatus;
use PHPUnit\Framework\TestCase;

final class ItemListFilterMapperTest extends TestCase
{
    public function testTheAddressCarriesTheFilterBothWays(): void
    {
        $mapper = new ItemListFilterMapper();
        $query = ['q' => 'laravel', 'section' => '0', 'status' => 'scheduled'];

        $filter = $mapper->fromQuery($query);

        self::assertSame('laravel', $filter->search);
        self::assertSame(0, $filter->section);
        self::assertSame(ItemStatus::SCHEDULED, $filter->status);
        self::assertSame($query, $mapper->toQuery($filter));
        self::assertSame('/admin/collections/1/items?q=laravel&section=0&status=scheduled', $mapper->url('/admin/collections/1/items', $filter));
    }

    public function testJunkReadsAsTheDefault(): void
    {
        $filter = (new ItemListFilterMapper())->fromQuery(['section' => '-1', 'status' => 'x', 'q' => ['a']]);

        self::assertTrue($filter->isEmpty());
    }
}
