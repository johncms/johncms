<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Domain\Enums;

/**
 * What the list of the hidden content of the forum shows: the posts, or the topics.
 */
enum HiddenListTab: string
{
    case POSTS = 'posts';
    case TOPICS = 'topics';
}
