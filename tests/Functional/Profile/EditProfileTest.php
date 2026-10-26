<?php

declare(strict_types=1);

namespace Tests\Functional\Profile;

use Johncms\Users\User;
use Symfony\Component\HttpFoundation\Response;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\FunctionalUserFactory;

/**
 * The owner of a profile edits the fields the form of the profile carries: the gender is a field
 * of the panel, so an ordinary user saves a profile without it.
 */
final class EditProfileTest extends FunctionalTestCase
{
    public function testAnOrdinaryUserSavesTheirProfileWithoutTheGender(): void
    {
        $owner = FunctionalUserFactory::create(['name' => 'Bilbo', 'sex' => 'm', 'imname' => 'Baggins', 'live' => 'The Shire', 'mail' => 'bilbo@example.com']);

        $form = (string) $this->handleRequest('/profile/' . $owner->id . '/edit', cookies: $this->actingAs($owner))->getContent();
        self::assertStringNotContainsString('name="sex"', $form);

        $response = $this->withConfig(['johncms' => ['user_email_confirmation' => 0]], fn (): Response => $this->postForm(
            '/profile/' . $owner->id . '/edit',
            [
                'imname'      => 'Baggins',
                'live'        => 'Bag End',
                'mail'        => 'bilbo@example.com',
                'mailvis'     => '0',
                'status'      => '',
                'dayb'        => '22',
                'monthb'      => '9',
                'yearofbirth' => '1968',
            ],
            $owner
        ));

        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        $owner = User::query()->findOrFail($owner->id);
        self::assertSame('Bag End', $owner->live);
        self::assertSame('m', $owner->sex, 'The stored gender is kept when the form of the profile does not carry it.');
    }

    public function testAnOrdinaryUserCannotResetTheirOwnGenderOrOtherAdminFields(): void
    {
        $owner = FunctionalUserFactory::create(['name' => 'Frodo', 'sex' => 'f']);

        $this->postForm(
            '/profile/' . $owner->id . '/edit',
            [
                'imname'      => '',
                'live'        => '',
                'mail'        => 'frodo@example.com',
                'mailvis'     => '0',
                'status'      => '',
                'dayb'        => '1',
                'monthb'      => '1',
                'yearofbirth' => '1990',
                'name'        => 'Smeagol',
                'sex'         => 'm',
                'karma_off'   => '1',
                'admin_notes' => 'Nothing to see',
            ],
            $owner
        );

        $owner = User::query()->findOrFail($owner->id);
        self::assertSame('Frodo', $owner->name);
        self::assertSame('f', $owner->sex);
        self::assertFalse($owner->karma_off);
        self::assertNull($owner->admin_notes);
    }
}