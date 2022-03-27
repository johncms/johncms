<?php

declare(strict_types=1);

namespace Johncms\Forum\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Johncms\Controller\BaseController;
use Johncms\Counters;
use Johncms\Forum\Models\ForumFile;
use Johncms\Forum\Models\ForumSection;
use Johncms\Online\Models\GuestSession;
use Johncms\Users\User;
use Johncms\Utility\Numbers;

class ForumSections extends BaseController
{
    protected string $moduleName = 'johncms/forum';
    protected string $baseUrl;

    public function __construct()
    {
        parent::__construct();
        $this->baseUrl = route('forum.index');
        $pageTitle = __('Forum');
        $this->navChain->add($pageTitle, $this->baseUrl);
        $this->metaTagManager->setAll($pageTitle);

        $config = di('config')['johncms'];
        $user = di(User::class);

        if (! $config['mod_forum'] && ! $user?->hasAnyRole()) {
            $error = __('Forum is closed');
        } elseif ($config['mod_forum'] === 1 && ! $user) {
            $error = __('For registered users only');
        }

        if (isset($error)) {
            echo $this->render->render(
                'system::pages/result',
                [
                    'title'    => $pageTitle,
                    'message'  => $error,
                    'type'     => 'alert-danger',
                    'back_url' => '/',
                ]
            );
            exit;
        }
    }

    public function index(Counters $counters): string
    {
        // Forum categories
        $sections = (new ForumSection())
            ->withCount('subsections', 'topics')
            ->with('subsections')
            ->where('parent', '=', 0)
            ->orWhereNull('parent')
            ->orderBy('sort')
            ->get();

        $forum_settings = di('config')['forum']['settings'];

        $online = [
            'online_u' => (new User())->online()->whereHas('activity', function (Builder $builder) {
                return $builder->where('route', 'like', 'forum.%');
            })->count(),
            'online_g' => (new GuestSession())->online()->where('route', 'like', 'forum.%')->count(),
        ];

        unset($_SESSION['fsort_id'], $_SESSION['fsort_users']);

        $this->metaTagManager->setKeywords($forum_settings['forum_keywords']);
        $this->metaTagManager->setDescription($forum_settings['forum_description']);

        return $this->render->render(
            'forum::index',
            [
                'title'        => __('Forum'),
                'page_title'   => __('Forum'),
                'sections'     => $sections,
                'online'       => $online,
                'files_count'  => $forum_settings['file_counters'] ? Numbers::formatNumber((new ForumFile())->count()) : 0,
                'unread_count' => Numbers::formatNumber($counters->forumUnreadCount()),
            ]
        );
    }
}
