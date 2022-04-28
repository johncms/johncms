<?php

declare(strict_types=1);

namespace Johncms\Forum\Ban;

use Johncms\Users\Ban\BanTypesInterface;

class ForumBans implements BanTypesInterface
{
    public const READ_ONLY = 'forum_read_only';
    public const ADD_MESSAGE = 'forum_add_messages';
    public const UPLOAD_PHOTOS = 'forum_upload_photos';
    public const CREATE_TOPICS = 'forum_create_topics';

    /**
     * @inheritDoc
     */
    public function getTypes(): array
    {
        return [
            [
                'name'        => self::READ_ONLY,
                'displayName' => d__('forum', 'Can\'t write anything'),
            ],
            [
                'name'        => self::ADD_MESSAGE,
                'displayName' => d__('forum', 'Add forum posts'),
            ],
            [
                'name'        => self::UPLOAD_PHOTOS,
                'displayName' => d__('forum', 'Upload photos on the forum'),
            ],
            [
                'name'        => self::CREATE_TOPICS,
                'displayName' => d__('forum', 'Create topics on the forum'),
            ],
        ];
    }
}
