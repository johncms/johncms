<?php

declare(strict_types=1);

namespace Johncms\Users\Ban;

interface BanTypesInterface
{
    /**
     * The method should return a list of bans.
     * Each element must contain a name and a displayName.
     * For example:
     *
     * [
     *      [
     *          'name'        => 'guestbook_write',
     *          'displayName' => d__('guestbook', 'Write in the guestbook'),
     *      ],
     *      [
     *          'name'        => 'guestbook_upload_photos',
     *          'displayName' => d__('guestbook', 'Upload photos in the guestbook'),
     *      ],
     * ]
     *
     * @return array
     */
    public function getTypes(): array;
}
