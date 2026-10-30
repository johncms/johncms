<?php

declare(strict_types=1);

namespace Tests\Functional\Admin;

use Johncms\Modules\Admin\Domain\Models\Counter;
use Symfony\Component\HttpFoundation\Response;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

/**
 * The analytics counters in the panel: the list of them in the order of the site, and what can be
 * done to one.
 */
final class CountersAdminTest extends FunctionalTestCase
{
    public function testTheListSaysWhereEveryCounterIsPrinted(): void
    {
        $this->counter('Metrica', mode: 2, consent: true);
        $this->counter('Home only', mode: 1);

        $content = $this->page('/admin/counters');

        self::assertStringContainsString('Metrica', $content);
        self::assertStringContainsString('На всех страницах', $content);
        self::assertStringContainsString('Только на главной', $content);
        self::assertStringContainsString('После согласия на cookie', $content);
    }

    public function testMovingIsOfferedOnlyWhereItDoesSomething(): void
    {
        $first = $this->counter('First', sort: 1);
        $last = $this->counter('Last', sort: 2);

        $content = $this->page('/admin/counters');

        self::assertStringNotContainsString('/admin/counters/' . $first . '/up"', $content);
        self::assertStringContainsString('/admin/counters/' . $first . '/down"', $content);
        self::assertStringContainsString('/admin/counters/' . $last . '/up"', $content);
        self::assertStringNotContainsString('/admin/counters/' . $last . '/down"', $content);
    }

    public function testTurningACounterOffReturnsToTheList(): void
    {
        $id = $this->counter('Metrica');

        $response = $this->handleRequest('/admin/counters/' . $id . '/toggle', 'POST', $this->withCsrfToken(['enabled' => '0']), cookies: $this->cookies());

        self::assertSame('/admin/counters', $response->headers->get('Location'));
        self::assertSame(0, Counter::query()->whereKey($id)->value('switch'));
    }

    public function testDeletingACounterAsksInTheDialog(): void
    {
        $id = $this->counter('Metrica');

        $dialog = (string) $this->handleRequest('/admin/counters/' . $id . '/delete', server: ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'], cookies: $this->cookies())->getContent();
        $this->handleRequest('/admin/counters/' . $id . '/delete', 'POST', $this->withCsrfToken(), cookies: $this->cookies());

        self::assertStringStartsWith('<form class="modal-content" method="post" action="/admin/counters/' . $id . '/delete">', trim($dialog));
        self::assertNull(Counter::query()->find($id));
    }

    public function testACounterIsSavedInOneStep(): void
    {
        $response = $this->handleRequest('/admin/counters', 'POST', $this->withCsrfToken([
            'name' => 'Metrica', 'link1' => '<script>ym()</script>', 'mode' => '2', 'switch' => '1', 'require_cookie_consent' => '1',
        ]), cookies: $this->cookies());

        $counter = Counter::query()->where('name', 'Metrica')->firstOrFail();
        self::assertSame('/admin/counters', $response->headers->get('Location'));
        self::assertSame(2, $counter->mode);
        self::assertSame(1, $counter->require_cookie_consent);
    }

    public function testAMissingCodeIsShownAtItsField(): void
    {
        $response = $this->handleRequest('/admin/counters', 'POST', $this->withCsrfToken(['name' => 'Empty', 'link1' => '']), cookies: $this->cookies());
        $content = (string) $response->getContent();

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringContainsString('Вставьте код счётчика', $content);
        self::assertMatchesRegularExpression('#name="link1" id="link1"#', $content);
        self::assertFalse(Counter::query()->where('name', 'Empty')->exists());
    }

    public function testTheSavedCodeIsPreviewedInAnIsolatedFrame(): void
    {
        $id = $this->counter('Metrica');

        $content = $this->page('/admin/counters/' . $id . '/edit');

        self::assertStringContainsString('sandbox="allow-scripts" srcdoc="&lt;span&gt;Metrica&lt;/span&gt;"', $content);
    }

    public function testTheOldPagesOfACounterAreGone(): void
    {
        $id = $this->counter('Metrica');

        self::assertSame(Response::HTTP_NOT_FOUND, $this->handleRequest('/admin/counters/' . $id, cookies: $this->cookies())->getStatusCode());
        // The public catch-all of the site takes the path for a GET only, so a POST meets 405.
        self::assertContains(
            $this->handleRequest('/admin/counters/preview', 'POST', $this->withCsrfToken(), cookies: $this->cookies())->getStatusCode(),
            [Response::HTTP_NOT_FOUND, Response::HTTP_METHOD_NOT_ALLOWED]
        );
    }

    private function counter(string $name, int $mode = 2, bool $consent = false, int $sort = 1): int
    {
        return Fixture::insert('cms_counters', [
            'sort' => $sort, 'name' => $name, 'link1' => '<span>' . $name . '</span>', 'link2' => '',
            'mode' => $mode, 'switch' => 1, 'require_cookie_consent' => $consent ? 1 : 0,
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function cookies(): array
    {
        return $this->actingAs(FunctionalUserFactory::createSupervisor());
    }

    private function page(string $uri): string
    {
        $response = $this->handleRequest($uri, cookies: $this->cookies());
        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        return (string) $response->getContent();
    }
}
