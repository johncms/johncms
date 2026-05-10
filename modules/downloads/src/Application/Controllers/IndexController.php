<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\Controllers;

use Downloads\Download;
use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Downloads\Domain\Models\DownloadCategory;
use Johncms\Modules\Downloads\Domain\Models\DownloadFile;
use Johncms\NavChain;
use Johncms\System\Http\Request;
use Johncms\System\Legacy\Tools;
use Johncms\System\View\Render;
use Johncms\Users\User;

final readonly class IndexController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private NavChain $navChain,
        private Tools $tools,
        private User $currentUser,
    ) {
        $this->controllerContext->initModule('downloads');
    }

    public function __invoke(): string
    {
        $id = max(0, (int) $this->request->getQuery('id', 0));
        $page = max(1, (int) $this->request->getQuery('page', 1));
        $kmess = $this->currentUser->config->kmess;

        $this->navChain->add(__('Downloads'), '/downloads/');

        $title = __('Downloads');
        $category = null;
        $canUpload = false;

        if ($id > 0) {
            $category = DownloadCategory::query()->find($id);
            if ($category === null || ! is_dir($category->dir)) {
                http_response_code(404);
                return $this->render->render('system::pages/result', [
                    'title'         => __('Wrong data'),
                    'type'          => 'alert-danger',
                    'message'       => __('The directory does not exist'),
                    'back_url'      => '/downloads/',
                    'back_url_name' => __('Downloads'),
                ]);
            }
            $this->buildNavChain($category);
            $title = $category->rus_name;
            $canUpload = (bool) $category->field && $this->currentUser->isValid();
        }

        $this->render->addData([
            'title'      => $title,
            'page_title' => $title,
        ]);

        $old = time() - 259200;
        $GLOBALS['old'] = $old;

        $totalNew = DownloadFile::query()
            ->where('type', 2)
            ->where('time', '>', $old)
            ->when($category !== null, static fn ($q) => $q->where('dir', 'like', $category->dir . '%'))
            ->count();

        $urls = [
            'downloads'   => '/downloads/',
            'new'         => $totalNew > 0 ? '/downloads/new/' . ($id ? '?id=' . $id : '') : '',
            'sort_action' => '',
        ];

        $totalCat = DownloadCategory::query()->where('refid', $id)->count();
        $categories = [];
        if ($totalCat > 0) {
            $hasEdit = $this->currentUser->rights === 4 || $this->currentUser->rights >= 6;
            DownloadCategory::query()->where('refid', $id)->orderBy('sort')->each(
                function (DownloadCategory $cat) use (&$categories, $hasEdit): void {
                    $categories[] = [
                        'id'         => $cat->id,
                        'rus_name'   => htmlspecialchars($cat->rus_name),
                        'desc'       => htmlspecialchars($cat->desc),
                        'field'      => $cat->field,
                        'text'       => $cat->text,
                        'total'      => $cat->total,
                        'url'        => '/downloads/?id=' . $cat->id,
                        'up_url'     => '/downloads/categories/' . $cat->id . '/edit?do=up',
                        'down_url'   => '/downloads/categories/' . $cat->id . '/edit?do=down',
                        'edit_url'   => '/downloads/categories/' . $cat->id . '/edit',
                        'delete_url' => '/downloads/categories/' . $cat->id . '/delete',
                        'has_edit'   => $hasEdit,
                    ];
                }
            );
        }

        $totalFiles = DownloadFile::query()->where('refid', $id)->where('type', '<', 3)->count();
        $files = [];

        if ($totalFiles > 0) {
            if ($totalFiles > 1) {
                if (! isset($_SESSION['sort_down'])) {
                    $_SESSION['sort_down'] = 0;
                }
                if (! isset($_SESSION['sort_down2'])) {
                    $_SESSION['sort_down2'] = 0;
                }

                if ($this->request->getMethod() === 'POST') {
                    $post = $this->request->getParsedBody();
                    if (isset($post['sort_down'])) {
                        $_SESSION['sort_down'] = $post['sort_down'] ? 1 : 0;
                    }
                    if (isset($post['sort_down2'])) {
                        $_SESSION['sort_down2'] = $post['sort_down2'] ? 1 : 0;
                    }
                }

                $urls['sort_action'] = '/downloads/?id=' . $id;
            }

            $sortColumn = ($_SESSION['sort_down'] ?? 0) ? 'name' : 'time';
            $sortDir = ($_SESSION['sort_down2'] ?? 0) ? 'asc' : 'desc';

            $rows = DownloadFile::query()
                ->where('refid', $id)
                ->where('type', '<', 3)
                ->orderBy('type')
                ->orderBy($sortColumn, $sortDir)
                ->offset(($page - 1) * $kmess)
                ->limit($kmess)
                ->get();

            foreach ($rows as $file) {
                $files[] = Download::displayFile($file->toArray());
            }
        }

        return $this->render->render('downloads::index', [
            'id'          => $id,
            'urls'        => $urls,
            'pagination'  => $this->tools->displayPagination(
                '/downloads/?id=' . $id . '&amp;',
                ($page - 1) * $kmess,
                $totalFiles,
                $kmess
            ),
            'files'       => $files,
            'total_files' => $totalFiles,
            'total_new'   => $totalNew,
            'categories'  => $categories,
            'total_cat'   => $totalCat,
            'can_upload'  => $canUpload,
        ]);
    }

    private function buildNavChain(DownloadCategory $category): void
    {
        $ancestors = [];
        $current = $category;
        while ($current->refid > 0) {
            $parent = DownloadCategory::query()->find($current->refid);
            if ($parent === null) {
                break;
            }
            $ancestors[] = $parent;
            $current = $parent;
        }
        foreach (array_reverse($ancestors) as $ancestor) {
            $this->navChain->add(htmlspecialchars($ancestor->rus_name), '/downloads/?id=' . $ancestor->id);
        }
        $this->navChain->add(htmlspecialchars($category->rus_name));
    }
}
