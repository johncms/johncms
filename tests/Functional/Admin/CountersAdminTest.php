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
