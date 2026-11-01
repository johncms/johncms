<?php

declare(strict_types=1);

namespace Tests\Functional\Admin;

use Tests\Functional\FunctionalTestCase;
use Tests\Support\FunctionalUserFactory;

final class SystemUpdateTaskTest extends FunctionalTestCase
{
    public function testMaintenanceOffersTheSystemUpdateTask(): void
    {
        $response = $this->handleRequest('/admin/maintenance', cookies: $this->actingAs(FunctionalUserFactory::createSupervisor()));
        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('system:update', (string) $response->getContent());
    }
}
