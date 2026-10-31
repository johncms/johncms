<?php

declare(strict_types=1);

namespace Tests\Functional\Admin;

use Dom\Element;
use Dom\HTMLDocument;
use Johncms\Auth\Authorization\CorePermissions;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

/**
 * The numbers on the dashboard of the panel: one card per thing a switched-on module counts, with
 * the change since the day before and a line of the last two weeks.
 *
 * A card is told by where it leads; the one of the visitors leads nowhere and has no line, as
 * only the last visit of an account is stored.
 */
final class DashboardMetricsTest extends FunctionalTestCase
{
    private const int DAY = 86400;

    public function testTheModulesPutTheirNumbersOnTheDashboard(): void
    {
        $page = $this->dashboard();

        foreach (['/admin/users', '/forum', '/guestbook', '/downloads', '/library', '/album'] as $url) {
            self::assertNotNull($this->card($page, $url), 'The card leading to ' . $url);
        }

        self::assertCount(7, $page->querySelectorAll('.admin-metric'));
    }

    public function testACardSaysTodayAndHowItMovedSinceTheDayBefore(): void
    {
        $now = time();
        Fixture::insert('forum_messages', ['topic_id' => 1, 'user_name' => 'Author', 'date' => $now - 60, 'text' => 'Today']);
        Fixture::insert('forum_messages', ['topic_id' => 1, 'user_name' => 'Author', 'date' => $now - 120, 'text' => 'Today']);
        Fixture::insert('forum_messages', ['topic_id' => 1, 'user_name' => 'Author', 'date' => $now - self::DAY - 60, 'text' => 'The day before']);
        Fixture::insert('forum_messages', ['topic_id' => 1, 'user_name' => 'Author', 'date' => $now - 20 * self::DAY, 'text' => 'Long ago']);

        $card = $this->card($this->dashboard(), '/forum');

        self::assertNotNull($card);
        self::assertSame('2', trim((string) $card->querySelector('.admin-stat__value')?->textContent));
        self::assertNotNull($card->querySelector('.admin-metric__delta--up'));
        self::assertCount(14, $card->querySelectorAll('.admin-metric__hit'));
    }

    public function testACardOfNothingOnBothDaysSaysNoChange(): void
    {
        $card = $this->card($this->dashboard(), '/guestbook');

        self::assertNotNull($card);
        self::assertSame('0', trim((string) $card->querySelector('.admin-stat__value')?->textContent));
        self::assertNull($card->querySelector('.admin-metric__delta'));
    }

    private function dashboard(): HTMLDocument
    {
        $admin = FunctionalUserFactory::createWithPermissions([CorePermissions::ADMIN_ACCESS]);
        $response = $this->handleRequest('/admin', cookies: $this->actingAs($admin));

        return HTMLDocument::createFromString((string) $response->getContent(), LIBXML_NOERROR);
    }

    private function card(HTMLDocument $page, string $url): ?Element
    {
        return $page->querySelector('a.admin-metric[href="' . $url . '"]');
    }
}
