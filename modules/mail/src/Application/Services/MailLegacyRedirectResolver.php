<?php

declare(strict_types=1);

namespace Johncms\Modules\Mail\Application\Services;

use Johncms\Users\User;

final class MailLegacyRedirectResolver
{
    public function __construct(
        private User $currentUser,
    ) {
    }

    public function resolve(array $query): ?string
    {
        $act = isset($query['act']) ? trim((string) $query['act']) : '';

        // act=index without id -> contacts list
        if ($act === 'index' && !isset($query['id'])) {
            return '/mail/';
        }

        // act=index with id and add parameter -> add contact form
        if ($act === 'index' && isset($query['id']) && isset($query['add'])) {
            $id = (int) $query['id'];
            if ($id > 0) {
                return '/mail/add/' . $id;
            }
        }

        // act=ignor -> blocklist
        if ($act === 'ignor' && !isset($query['id'])) {
            return '/mail/blocklist';
        }

        // act=ignor with id and add -> block user
        if ($act === 'ignor' && isset($query['id']) && isset($query['add'])) {
            $id = (int) $query['id'];
            if ($id > 0) {
                return '/mail/block/' . $id;
            }
        }

        // act=ignor with id (without add) -> maybe unblock? but legacy doesn't have separate unblock page
        // We'll ignore for now

        // act=delete -> delete message
        if ($act === 'delete' && isset($query['id'])) {
            $id = (int) $query['id'];
            if ($id > 0) {
                return '/mail/delete/' . $id;
            }
        }

        // act=deluser -> delete contact
        if ($act === 'deluser' && isset($query['id'])) {
            $id = (int) $query['id'];
            if ($id > 0) {
                return '/mail/delete-contact/' . $id;
            }
        }

        // act=input -> incoming messages
        if ($act === 'input') {
            return '/mail/incoming/';
        }

        // act=output -> outgoing messages (to be implemented)
        if ($act === 'output') {
            // TODO: implement outgoing controller
            return null;
        }

        return null;
    }
}
