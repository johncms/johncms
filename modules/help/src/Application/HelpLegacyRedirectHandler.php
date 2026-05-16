<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Modules\Help\Application;

use Johncms\System\Http\Request;

final readonly class HelpLegacyRedirectHandler
{
    public function __construct(private Request $request)
    {
    }

    public function handle(): void
    {
        $act = (string) $this->request->getQuery('act', '');

        if ($act === '') {
            return;
        }

        $id     = (int) $this->request->getQuery('id', 0, FILTER_VALIDATE_INT);
        $avatar = (int) $this->request->getQuery('avatar', 0, FILTER_VALIDATE_INT);
        $cat    = trim((string) $this->request->getQuery('cat', ''));

        $url = match ($act) {
            'forum'       => '/help/forum/',
            'tags'        => '/help/',
            'smilies'     => '/help/smilies/',
            'my_smilies'  => '/help/smilies/my/',
            'admsmilies'  => '/help/smilies/admin/',
            'usersmilies' => $cat !== '' ? '/help/smilies/' . urlencode($cat) . '/' : '/help/smilies/',
            'avatars'     => $this->resolveAvatarsUrl($id, $avatar),
            default       => null,
        };

        if ($url !== null) {
            header('Location: ' . $url, true, 301);
            exit;
        }
    }

    private function resolveAvatarsUrl(int $id, int $avatar): string
    {
        if ($id > 0 && $avatar > 0) {
            return '/help/avatars/' . $id . '/set/' . $avatar . '/';
        }

        if ($id > 0) {
            return '/help/avatars/' . $id . '/';
        }

        return '/help/avatars/';
    }
}
