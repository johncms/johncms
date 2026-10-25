<?php

declare(strict_types=1);

namespace Tests\Functional\Community;

use Johncms\Auth\Authorization\UserRole;
use Symfony\Component\HttpFoundation\Response;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\FunctionalUserFactory;

/**
 * The administration of the community is whoever holds a role in force, from the highest role
 * down. A grant that ran out neither lists an account nor raises it.
 */
final class AdministrationTest extends FunctionalTestCase
{
    public function testAGrantThatRanOutLeavesTheAdministration(): void
    {
        $role = FunctionalUserFactory::createRole([], 20);
        $serving = FunctionalUserFactory::create(['name' => 'Faramir']);
        $former = FunctionalUserFactory::create(['name' => 'Denethor']);
        FunctionalUserFactory::grant($serving->id, $role->slug);
        FunctionalUserFactory::grant($former->id, $role->slug);
        $this->runOut($former->id, $role->id);

        $body = $this->page();

        self::assertStringContainsString('Faramir', $body);
        self::assertStringNotContainsString('Denethor', $body);
    }

    public function testTheOrderFollowsTheRolesInForce(): void
    {
        $high = FunctionalUserFactory::createRole([], 70);
        $middle = FunctionalUserFactory::createRole([], 50);
        $low = FunctionalUserFactory::createRole([], 20);
        $steward = FunctionalUserFactory::create(['name' => 'Boromir']);
        $captain = FunctionalUserFactory::create(['name' => 'Beregond']);
        FunctionalUserFactory::grant($steward->id, $high->slug);
        FunctionalUserFactory::grant($steward->id, $low->slug);
        FunctionalUserFactory::grant($captain->id, $middle->slug);
        $this->runOut($steward->id, $high->id);

        $body = $this->page();

        self::assertNotFalse(strpos($body, 'Boromir'));
        self::assertLessThan(strpos($body, 'Boromir'), strpos($body, 'Beregond'), 'The role that ran out no longer puts its holder first.');
    }

    private function runOut(int $userId, int $roleId): void
    {
        UserRole::query()->where('user_id', '=', $userId)->where('role_id', '=', $roleId)->update(['expires_at' => time() - 1]);
    }

    private function page(): string
    {
        $response = $this->handleRequest('/community/administration', cookies: $this->actingAs(FunctionalUserFactory::create()));
        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        return (string) $response->getContent();
    }
}
