<?php

declare(strict_types=1);

namespace Johncms\Forum\Ban;

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
                'name'        => 'forum_read_only',
                'displayName' => d__('forum', 'Can\'t write anything'),
            ],
            [
                'name'        => 'forum_add_messages',
                'displayName' => d__('forum', 'Add forum posts'),
            ],
            [
                'name'        => 'forum_upload_photos',
                'displayName' => d__('forum', 'Upload photos on the forum'),
            ],
            [
                'name'        => 'forum_create_topics',
                'displayName' => d__('forum', 'Create topics on the forum'),
            ],
        ];
    }
}
