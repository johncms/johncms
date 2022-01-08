<?php

declare(strict_types=1);

namespace Johncms\Guestbook\Ban;

use Johncms\Users\Ban\BanTypesInterface;

class BanTypes implements BanTypesInterface
{
    /**
     * @inheritDoc
     */
    public function getTypes(): array
    {
        return [
            [
                'name'        => 'guestbook_write',
                'displayName' => d__('guestbook', 'Write in the guestbook'),
            ],
            [
                'name'        => 'guestbook_upload_photos',
                'displayName' => d__('guestbook', 'Upload photos in the guestbook'),
            ],
        ];
    }
}
