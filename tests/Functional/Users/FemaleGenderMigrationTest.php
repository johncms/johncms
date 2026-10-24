<?php

declare(strict_types=1);

namespace Tests\Functional\Users;

use Johncms\Database\ConnectionInterface;
use Johncms\Database\Migrations\Migration;
use Johncms\Database\Schema\SchemaInterface;
use Johncms\Users\User;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\FunctionalUserFactory;

final class FemaleGenderMigrationTest extends FunctionalTestCase
{
    public function testTheGenderTheSiteDoesNotKnowBecomesFemale(): void
    {
        $maria = FunctionalUserFactory::create(['sex' => 'f']);
        $alex = FunctionalUserFactory::create(['sex' => 'm']);

        /** @var Migration $migration */
        $migration = require ROOT_PATH . 'system/migrations/2026_10_24_193856_store_female_gender_as_zh.php';
        $migration->bind($this->container()->get(SchemaInterface::class), $this->container()->get(ConnectionInterface::class));
        $migration->up();

        self::assertSame('zh', User::query()->findOrFail($maria->id)->sex);
        self::assertSame('m', User::query()->findOrFail($alex->id)->sex);
    }
}
