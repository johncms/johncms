<?php

declare(strict_types=1);

namespace Tests\Functional\Forum;

use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

/**
 * The forum tells a member who may not write there that it is read-only for them. A guest is told
 * nothing: writing takes signing in for everybody.
 */
final class ReadOnlyBannerTest extends FunctionalTestCase
{
    private const string BANNER = '<div class="alert alert-danger">';

    public function testAGuestIsNotToldTheForumIsReadOnly(): void
    {
        self::assertStringNotContainsString(self::BANNER, $this->forum());
    }

    public function testAMemberWhoMayWriteIsNotToldEither(): void
    {
        self::assertStringNotContainsString(self::BANNER, $this->forum(FunctionalUserFactory::create()->id));
    }

    public function testAMemberBannedFromTheForumIsTold(): void
    {
        $member = FunctionalUserFactory::create();
        Fixture::insert(
            'cms_ban_users',
            ['user_id' => $member->id, 'ban_time' => time() + 3600, 'ban_while' => time(), 'ban_type' => 11, 'ban_reason' => 'Spam']
        );

        self::assertStringContainsString(self::BANNER, $this->forum($member->id));
    }

    private function forum(?int $as = null): string
    {
        return (string) $this->handleRequest('/forum/', cookies: $as === null ? [] : $this->actingAs($as))->getContent();
    }
}
