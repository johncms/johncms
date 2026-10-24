<?php

declare(strict_types=1);

namespace Tests\Functional\Admin;

use Johncms\Users\User;
use Symfony\Component\HttpFoundation\Response;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\FunctionalUserFactory;

/**
 * An account that has to confirm its e-mail before it signs in: the panel lists such accounts,
 * confirms the e-mail on behalf of the owner, and does not offer to browse as one meanwhile.
 */
final class EmailConfirmationTest extends FunctionalTestCase
{
    public function testThePageOfTheAccountOffersTheConfirmationAndNotBrowsingAsIt(): void
    {
        $member = FunctionalUserFactory::create(['email_confirmed' => null]);

        $page = $this->withConfirmation(true, fn (): string => $this->pageOf('/admin/users/' . $member->id));

        self::assertStringContainsString('action="/admin/registrations/confirm-email"', $page);
        self::assertStringNotContainsString('action="/impersonation/start/' . $member->id . '"', $page);
    }

    public function testConfirmingTheEmailLetsTheAccountIn(): void
    {
        $member = FunctionalUserFactory::create(['email_confirmed' => null, 'confirmation_code' => 'abc']);
        $back = '/admin/users/' . $member->id;

        $response = $this->withConfirmation(true, fn (): Response => $this->postForm('/admin/registrations/confirm-email', ['id' => (string) $member->id, 'back' => $back], FunctionalUserFactory::createSupervisor()));

        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        self::assertSame($back, $response->headers->get('Location'));
        $member = User::query()->findOrFail($member->id);
        self::assertTrue($member->email_confirmed);
        self::assertNull($member->confirmation_code);
        self::assertTrue($this->withConfirmation(true, static fn (): bool => $member->is_valid));
    }

    public function testTheListShowsTheAccountsThatHaveNotConfirmedTheirEmail(): void
    {
        FunctionalUserFactory::create(['name' => 'Pippin', 'email_confirmed' => null]);
        FunctionalUserFactory::create(['name' => 'Merry']);

        $list = $this->withConfirmation(true, fn (): string => $this->pageOf('/admin/users?status=unconfirmed', live: true));

        self::assertStringContainsString('Pippin', $list);
        self::assertStringNotContainsString('Merry', $list);
        self::assertStringContainsString('action="/admin/registrations/confirm-email"', $list);
    }

    public function testASiteThatDoesNotAskForTheConfirmationOffersNoneOfIt(): void
    {
        $member = FunctionalUserFactory::create(['email_confirmed' => null]);

        [$page, $list] = $this->withConfirmation(false, fn (): array => [
            $this->pageOf('/admin/users/' . $member->id),
            $this->pageOf('/admin/users', live: true),
        ]);

        self::assertStringNotContainsString('confirm-email', $page);
        self::assertStringContainsString('action="/impersonation/start/' . $member->id . '"', $page);
        self::assertStringNotContainsString('status=unconfirmed', $list);
    }

    /**
     * @param bool $live The regions of the list alone, as the live list asks for them: the page
     *                   around them carries the query log of the process when debugging is on.
     */
    private function pageOf(string $uri, bool $live = false): string
    {
        $response = $this->handleRequest(
            $uri,
            server: $live ? ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'] : [],
            cookies: $this->actingAs(FunctionalUserFactory::createSupervisor())
        );

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        return (string) $response->getContent();
    }

    /**
     * @template T
     * @param callable(): T $test
     * @return T
     */
    private function withConfirmation(bool $required, callable $test): mixed
    {
        return $this->withConfig(['johncms' => ['user_email_confirmation' => (int) $required]], $test);
    }
}
