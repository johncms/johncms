<?php

declare(strict_types=1);

namespace Tests\Unit\Database\Query;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Johncms\Database\Query\DailyCounts;
use PHPUnit\Framework\TestCase;
use Tests\Support\BootsInMemoryDatabase;

final class DailyCountsTest extends TestCase
{
    use BootsInMemoryDatabase;

    private const int START = 1_000_000;
    private const int DAY = 86400;

    protected function setUp(): void
    {
        $this->bootDatabase();
        Capsule::schema()->create('events', static function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('time');
            $table->boolean('hidden')->default(false);
        });
    }

    protected function tearDown(): void
    {
        $this->shutdownDatabase();
    }

    public function testTheRowsAreCountedByTheDayTheyFallInto(): void
    {
        $this->insert(self::START, self::START + self::DAY - 1, self::START + 2 * self::DAY, self::START + 2 * self::DAY + 5, self::START + 2 * self::DAY + 9);

        self::assertSame([2, 0, 3], DailyCounts::count($this->events(), 'time', self::START, 3));
    }

    public function testTheRowsOutsideThePeriodAreLeftOut(): void
    {
        $this->insert(self::START - 1, self::START + 2 * self::DAY);

        self::assertSame([0, 0], DailyCounts::count($this->events(), 'time', self::START, 2));
    }

    public function testTheConditionsOfTheQueryStay(): void
    {
        $this->insert(self::START, self::START + 10);
        Capsule::table('events')->where('time', self::START)->update(['hidden' => true]);

        self::assertSame([1], DailyCounts::count($this->events()->where('hidden', false), 'time', self::START, 1));
    }

    private function insert(int ...$times): void
    {
        Capsule::table('events')->insert(array_map(static fn (int $time): array => ['time' => $time], $times));
    }

    /**
     * @return Builder<covariant Model>
     */
    private function events(): Builder
    {
        $model = new class extends Model {
            protected $table = 'events';
        };

        return $model->newQuery();
    }
}
