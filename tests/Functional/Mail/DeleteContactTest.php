<?php

declare(strict_types=1);

namespace Tests\Functional\Mail;

use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

/**
 * Deleting a contact asks first, in two lines: what goes with it and whether to go on.
 */
final class DeleteContactTest extends FunctionalTestCase
{
    public function testTheQuestionBreaksTheLineInsteadOfPrintingTheTag(): void
    {
        $owner = FunctionalUserFactory::create();
        $contact = FunctionalUserFactory::create();
        Fixture::insert('cms_contact', ['user_id' => $owner->id, 'from_id' => $contact->id, 'time' => time()]);

        $page = (string) $this->handleRequest('/mail/delete-contact/' . $contact->id, cookies: $this->actingAs($owner))->getContent();

        self::assertMatchesRegularExpression('#[^\s<>]\s*<br>\s*[^\s<>]#u', $page);
        self::assertStringNotContainsString('&lt;br&gt;', $page);
    }
}
