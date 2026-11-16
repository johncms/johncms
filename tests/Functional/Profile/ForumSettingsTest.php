<?php

declare(strict_types=1);

namespace Tests\Functional\Profile;

use Johncms\Users\User;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\FunctionalUserFactory;

/**
 * The forum settings of the profile hold the order of the posts only: what an older account kept
 * besides it goes away with the next save.
 */
final class ForumSettingsTest extends FunctionalTestCase
{
    public function testSavingKeepsOnlyTheOrderOfThePosts(): void
    {
        $member = FunctionalUserFactory::create();
        $member->update(['set_forum' => ['farea' => 1, 'upfp' => 0, 'preview' => 1, 'postclip' => 2]]);

        $response = $this->postForm('/profile/settings/forum', ['upfp' => '1'], $member);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(['upfp' => true], User::query()->findOrFail($member->id)->set_forum);
    }

    public function testThePageOffersTheOrderOfThePostsOnly(): void
    {
        $member = FunctionalUserFactory::create();
        $member->update(['set_forum' => ['farea' => 1, 'upfp' => 1, 'postclip' => 2]]);

        $html = (string) $this->handleRequest('/profile/settings/forum', cookies: $this->actingAs($member))->getContent();

        self::assertStringContainsString('name="upfp"', $html);
        self::assertStringNotContainsString('name="farea"', $html);
        self::assertStringNotContainsString('name="postclip"', $html);
    }
}
