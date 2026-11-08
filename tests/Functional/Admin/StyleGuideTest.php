<?php

declare(strict_types=1);

namespace Tests\Functional\Admin;

use Johncms\Auth\Authorization\CorePermissions;
use Symfony\Component\HttpFoundation\Response;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\FunctionalUserFactory;

/**
 * The style guide renders every example and prints the source of each under it. The source has to
 * reach the page as text: printed raw, the markup of an example would render a second time instead
 * of being shown.
 */
final class StyleGuideTest extends FunctionalTestCase
{
    public function testAnyoneLetIntoThePanelOpensIt(): void
    {
        $moderator = FunctionalUserFactory::createWithPermissions([CorePermissions::ADMIN_ACCESS]);

        $response = $this->handleRequest('/admin/style-guide', cookies: $this->actingAs($moderator));

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testAnExampleIsRenderedAndItsSourceIsShownAsText(): void
    {
        $response = $this->handleRequest(
            '/admin/style-guide',
            cookies: $this->actingAs(FunctionalUserFactory::createSupervisor())
        );

        $body = (string) $response->getContent();

        self::assertStringContainsString('<button type="button" class="btn btn-primary">btn-primary</button>', $body);
        self::assertStringContainsString('&lt;button type=&quot;button&quot; class=&quot;btn btn-primary&quot;&gt;btn-primary&lt;/button&gt;', $body);
        self::assertStringContainsString('{% include &#039;@admin/components/ui/icon.twig&#039;', $body, 'The Twig of an example, not its output.');
        self::assertSame(4, substr_count($body, 'data-admin-toast-show="'), 'Every kind of toast can be shown from the guide.');
        self::assertStringContainsString('data-admin-toasts', $body, 'The stack of toasts is on every page of the panel from the start.');
    }
}
