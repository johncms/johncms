<?php

declare(strict_types=1);

namespace Tests\Functional\Admin;

use Johncms\Auth\Authorization\CorePermissions;
use Johncms\Users\Karma;
use Johncms\Users\User;
use Symfony\Component\HttpFoundation\Response;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

/**
 * The settings of the karma and clearing it: the form shows what is configured, clearing is
 * confirmed in a dialog and wipes every vote, and only those who manage the site get there.
 */
final class KarmaSettingsTest extends FunctionalTestCase
{
    public function testTheFormShowsTheConfiguredSettings(): void
    {
        $content = $this->withConfig(
            ['johncms' => ['karma' => ['on' => 1, 'adm' => 1, 'karma_points' => 7, 'forum' => 25]]],
            fn(): string => $this->page('/admin/karma')
        );

        self::assertStringContainsString('name="on" value="1" id="karma-on" checked', $content);
        self::assertStringContainsString('name="adm" value="1" id="karma-adm" checked', $content);
        self::assertStringContainsString('name="karma_points" id="karma-points" value="7"', $content);
        self::assertStringContainsString('name="forum" id="karma-forum" value="25"', $content);
    }

    public function testASwitchedOffSettingIsShownOff(): void
    {
        $content = $this->withConfig(
            ['johncms' => ['karma' => ['on' => 0, 'adm' => 0]]],
            fn(): string => $this->page('/admin/karma')
        );

        self::assertStringContainsString('name="on" value="1" id="karma-on">', $content);
        self::assertStringContainsString('name="adm" value="1" id="karma-adm">', $content);
    }

    public function testTheModalGetsTheDialogWithTheVotesItWouldDelete(): void
    {
        $member = FunctionalUserFactory::create();
        $this->vote($member);
        $this->vote($member);

        $content = trim($this->page('/admin/karma/clear', ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']));

        self::assertStringStartsWith('<form class="modal-content" method="post" action="/admin/karma/reset">', $content);
        // The language of the page depends on what ran before, so only the number is looked for.
        self::assertMatchesRegularExpression('~<p>[^<]*\\b2\\b[^<]*</p>~u', $content);
    }

    public function testAPlainVisitGetsTheDialogAsAPage(): void
    {
        $content = $this->page('/admin/karma/clear');

        self::assertStringContainsString('admin-dialog-page', $content);
        self::assertStringContainsString('href="/admin/karma" class="btn-close"', $content);
    }

    public function testClearingDeletesEveryVoteAndZeroesTheKarma(): void
    {
        $member = FunctionalUserFactory::create(['karma_plus' => 5, 'karma_minus' => 2]);
        $this->vote($member);

        $response = $this->postForm('/admin/karma/reset', [], FunctionalUserFactory::createSupervisor());

        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        self::assertSame('/admin/karma', $response->headers->get('Location'));
        self::assertSame(0, Karma::query()->count());
        $member = User::query()->findOrFail($member->id);
        self::assertSame([0, 0], [(int) $member->karma_plus, (int) $member->karma_minus]);
    }

    public function testAModeratorOfThePanelMayNeitherOpenNorClearTheKarma(): void
    {
        $moderator = FunctionalUserFactory::createWithPermissions([CorePermissions::ADMIN_ACCESS], level: 60);
        $this->vote(FunctionalUserFactory::create());

        foreach (['/admin/karma', '/admin/karma/clear'] as $uri) {
            self::assertSame(Response::HTTP_FORBIDDEN, $this->handleRequest($uri, cookies: $this->actingAs($moderator))->getStatusCode(), $uri);
        }
        // Asked only once the pages are closed: a save that got through would write the config file.
        self::assertSame(Response::HTTP_FORBIDDEN, $this->postForm('/admin/karma', ['karma_points' => 100], $moderator)->getStatusCode());
        self::assertSame(Response::HTTP_FORBIDDEN, $this->postForm('/admin/karma/reset', [], $moderator)->getStatusCode());
        self::assertSame(1, Karma::query()->count());
    }

    /**
     * @param array<string, string> $server
     */
    private function page(string $uri, array $server = []): string
    {
        $response = $this->handleRequest($uri, server: $server, cookies: $this->actingAs(FunctionalUserFactory::createSupervisor()));
        self::assertSame(Response::HTTP_OK, $response->getStatusCode(), $uri);

        return (string) $response->getContent();
    }

    private function vote(User $for): void
    {
        Fixture::insert('karma_users', [
            'user_id'    => 0,
            'name'       => 'voter',
            'karma_user' => $for->id,
            'points'     => 1,
            'type'       => 1,
            'time'       => time(),
            'text'       => '',
        ]);
    }
}
