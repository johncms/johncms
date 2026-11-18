<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Services;

use Johncms\Modules\Forum\Application\ForumUtils;
use Johncms\Modules\Forum\Domain\Models\ForumTopic;
use Johncms\NavChain;

/**
 * The crumbs of a page about a topic: the forum, the sections down to the topic, the topic and
 * what the page does with it.
 */
final readonly class ForumTopicBreadcrumbs
{
    public function __construct(private NavChain $navChain)
    {
    }

    public function add(ForumTopic $topic, string $topicUrl, string $current): void
    {
        $this->navChain->add(__('Forum'), '/forum/');
        ForumUtils::buildBreadcrumbs($topic->section_id, $topic->name, $topicUrl);
        $this->navChain->add($current);
    }
}
