<?php

declare(strict_types=1);

namespace Tests\Functional\Users;

use Johncms\Database\ConnectionInterface;
use Johncms\Database\Migrations\Migration;
use Johncms\Database\Schema\SchemaInterface;
use Johncms\Users\Gender;
use Johncms\Users\User;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\FunctionalUserFactory;

/**
 * The genders an account may name come from one list, and what older versions stored is brought
 * in line with it.
 */
final class GenderTest extends FunctionalTestCase
{
    public function testAnAccountReadsItsGenderFromTheList(): void
    {
        self::assertSame(Gender::Female, FunctionalUserFactory::create(['sex' => 'f'])->gender);
        self::assertNull(FunctionalUserFactory::create(['sex' => ''])->gender);
    }

    public function testTheProfileTellsTheGender(): void
    {
        $member = FunctionalUserFactory::create(['sex' => 'f']);

        $response = $this->handleRequest('/profile/' . $member->id, cookies: $this->actingAs(FunctionalUserFactory::create()));

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString(Gender::Female->title(), (string) $response->getContent());
    }

    public function testTheFormOfThePanelOffersEveryGenderAndSavesTheChosenOne(): void
    {
        $member = FunctionalUserFactory::create(['sex' => 'm']);
        $cookies = $this->actingAs(FunctionalUserFactory::createSupervisor());

        $form = (string) $this->handleRequest('/admin/users/' . $member->id . '/edit', cookies: $cookies)->getContent();
        foreach (Gender::cases() as $gender) {
            self::assertStringContainsString('<option value="' . $gender->value . '"', $form);
        }

        $this->handleRequest(
            '/admin/users/' . $member->id . '/edit',
            'POST',
            $this->withCsrfToken(['name' => $member->name, 'sex' => 'f', 'mail' => '', 'imname' => '', 'live' => '']),
            cookies: $cookies
        );

        self::assertSame(Gender::Female, User::query()->findOrFail($member->id)->gender);
    }

    public function testTheFemaleGenderOfOlderVersionsIsStoredAsF(): void
    {
        $maria = FunctionalUserFactory::create(['sex' => 'zh']);
        $alex = FunctionalUserFactory::create(['sex' => 'm']);

        /** @var Migration $migration */
        $migration = require ROOT_PATH . 'system/migrations/2026_10_24_194354_store_female_gender_as_f.php';
        $migration->bind($this->container()->get(SchemaInterface::class), $this->container()->get(ConnectionInterface::class));
        $migration->up();

        self::assertSame('f', User::query()->findOrFail($maria->id)->sex);
        self::assertSame('m', User::query()->findOrFail($alex->id)->sex);
    }
}
