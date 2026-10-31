<?php

declare(strict_types=1);

namespace Tests\Functional\Admin;

use Johncms\Notifications\Notification;
use Johncms\Users\User;
use Tests\Functional\FunctionalTestCase;
use Tests\Support\Fixture;
use Tests\Support\FunctionalUserFactory;

final class NotificationsTest extends FunctionalTestCase
{
    public function testPanelShowsExistingMarkupAndMarksOnlyOwnEventsAsRead(): void
    {
        $admin = FunctionalUserFactory::createSupervisor();
        $other = FunctionalUserFactory::create();
        $ownId = $this->notification($admin->id, 'Own event');
        $otherId = $this->notification($other->id, 'Other event');

        $response = $this->handleRequest(
            '/admin/notifications',
            server: ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'],
            cookies: $this->actingAs($admin),
            catch: false,
        );

        self::assertSame(200, $response->getStatusCode());
        $body = (string) $response->getContent();
        self::assertStringContainsString('offcanvas-header', $body);
        self::assertStringNotContainsString('<html', $body);
        self::assertStringContainsString('href="/profile/1/karma"', $body);
        self::assertStringContainsString('Own event', $body);
        self::assertStringNotContainsString('Other event', $body);
        self::assertStringContainsString('action="/admin/notifications/clear"', $body);
        self::assertStringContainsString('name="csrf_token"', $body);
        self::assertNotNull(Notification::query()->withoutGlobalScopes()->findOrFail($ownId)->read_at);
        self::assertNull(Notification::query()->withoutGlobalScopes()->findOrFail($otherId)->read_at);
    }

    public function testOnlyEventsNotSeenBeforeAreMarkedNew(): void
    {
        $admin = FunctionalUserFactory::createSupervisor();
        $this->notification($admin->id, 'Fresh event');
        $cookies = $this->actingAs($admin);
        $headers = ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'];

        $first = (string) $this->handleRequest('/admin/notifications', server: $headers, cookies: $cookies)->getContent();
        $second = (string) $this->handleRequest('/admin/notifications', server: $headers, cookies: $cookies)->getContent();

        self::assertStringContainsString('admin-notifications__event--unread', $first);
        self::assertStringNotContainsString('admin-notifications__event--unread', $second);
    }

    public function testSettingsStayInThePanel(): void
    {
        $admin = FunctionalUserFactory::createSupervisor();

        $page = $this->handleRequest('/admin/notifications/settings', cookies: $this->actingAs($admin));
        self::assertSame(200, $page->getStatusCode());
        self::assertStringContainsString('admin-shell', (string) $page->getContent());
        self::assertStringContainsString('name="show_forum_unread"', (string) $page->getContent());

        $response = $this->postForm('/admin/notifications/settings', [], $admin);

        self::assertSame(302, $response->getStatusCode());
        self::assertSame('/admin/notifications/settings', $response->headers->get('Location'));
        self::assertFalse(User::query()->findOrFail($admin->id)->notification_settings['show_forum_unread']);
    }

    public function testDirectVisitUsesTheAdminLayout(): void
    {
        $response = $this->handleRequest('/admin/notifications', cookies: $this->actingAs(FunctionalUserFactory::createSupervisor()));

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('admin-shell', (string) $response->getContent());
        self::assertStringContainsString('data-admin-offcanvas-target="admin-notifications"', (string) $response->getContent());
    }

    public function testClearKeepsOtherUsersEventsAndReturnsToThePanel(): void
    {
        $admin = FunctionalUserFactory::createSupervisor();
        $ownId = $this->notification($admin->id, 'Own event');
        $otherId = $this->notification(FunctionalUserFactory::create()->id, 'Other event');

        $response = $this->postForm('/admin/notifications/clear', [], $admin);

        self::assertSame(302, $response->getStatusCode());
        self::assertSame('/admin/notifications', $response->headers->get('Location'));
        self::assertNull(Notification::query()->withoutGlobalScopes()->find($ownId));
        self::assertNotNull(Notification::query()->withoutGlobalScopes()->find($otherId));
    }

    public function testMemberCannotOpenTheAdminNotifications(): void
    {
        $response = $this->handleRequest('/admin/notifications', cookies: $this->actingAs(FunctionalUserFactory::create()));

        self::assertSame(403, $response->getStatusCode());
    }

    public function testPublicNotificationsKeepTheirOwnLayoutAndActions(): void
    {
        $member = FunctionalUserFactory::create();
        $this->notification($member->id, 'Public event');
        $response = $this->handleRequest('/notifications', cookies: $this->actingAs($member));

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('Public event', (string) $response->getContent());
        self::assertStringContainsString('action="/notifications/clear/"', (string) $response->getContent());
        self::assertStringNotContainsString('admin-shell', (string) $response->getContent());
    }

    public function testXhrClearRefreshesThePanelWithSuccessAndEmptyPersonalHistory(): void
    {
        $admin = FunctionalUserFactory::createSupervisor();
        $this->notification($admin->id, 'Event to remove');
        $cookies = $this->actingAs($admin);
        $headers = ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'];

        $response = $this->handleRequest(
            '/admin/notifications/clear',
            'POST',
            $this->withCsrfToken(),
            server: $headers,
            cookies: $cookies,
        );

        self::assertSame(302, $response->getStatusCode());
        self::assertSame('/admin/notifications', $response->headers->get('Location'));

        // Fetch follows the POST redirect with the same XHR header.
        $refreshed = $this->handleRequest('/admin/notifications', server: $headers, cookies: $cookies);
        self::assertSame(200, $refreshed->getStatusCode());
        $body = (string) $refreshed->getContent();
        self::assertStringContainsString('offcanvas-header', $body);
        self::assertStringNotContainsString('<html', $body);
        self::assertStringContainsString('data-notifications-unread=', $body);
        self::assertStringContainsString(d__('notifications', 'Notifications are cleared!'), $body);
        self::assertStringContainsString(d__('notifications', 'No personal notifications.'), $body);
        self::assertStringNotContainsString('Event to remove', $body);
        self::assertStringNotContainsString('data-notifications-clear-start', $body);
    }

    public function testClearWithoutCsrfDoesNotDeleteEvents(): void
    {
        $admin = FunctionalUserFactory::createSupervisor();
        $id = $this->notification($admin->id, 'Keep this event');
        $response = $this->handleRequest(
            '/admin/notifications/clear',
            'POST',
            server: ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'],
            cookies: $this->actingAs($admin),
        );

        self::assertSame(403, $response->getStatusCode());
        self::assertNotNull(Notification::query()->withoutGlobalScopes()->find($id));
    }

    private function notification(int $userId, string $message): int
    {
        return Fixture::insert('notifications', [
            'user_id' => $userId,
            'created_at' => '2026-10-31 10:00:00',
            'module' => 'karma',
            'event_type' => 'new_vote',
            'fields' => json_encode([
                'user_name' => 'Sender',
                'karma_url' => '/profile/1/karma',
                'vote_points' => '1',
                'message' => $message,
            ], JSON_THROW_ON_ERROR),
        ]);
    }
}
