<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\Controllers;

use Downloads\Screen;
use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Downloads\Domain\Models\DownloadBookmark;
use Johncms\Modules\Downloads\Domain\Models\DownloadCategory;
use Johncms\Modules\Downloads\Domain\Models\DownloadComment;
use Johncms\Modules\Downloads\Domain\Models\DownloadFile;
use Johncms\Modules\Downloads\Domain\Models\DownloadMoreFile;
use Johncms\System\Http\Request;
use Johncms\System\Http\Session;
use Johncms\System\View\Render;

final readonly class DeleteFileController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private Session $session,
    ) {
        $this->controllerContext->initModule('downloads');
    }

    public function __invoke(int $id): string
    {
        $file = DownloadFile::query()
            ->where('id', $id)
            ->whereIn('type', [2, 3])
            ->first();

        if ($file === null || ! is_file($file->dir . '/' . $file->name)) {
            http_response_code(404);
            return $this->render->render('system::pages/result', [
                'title'         => __('File not found'),
                'type'          => 'alert-danger',
                'message'       => __('File not found'),
                'back_url'      => '/downloads/',
                'back_url_name' => __('Downloads'),
            ]);
        }

        if ($this->request->getMethod() === 'POST') {
            $post = $this->request->getParsedBody();
            $sessionToken = $this->session->get('delete_token');

            if (
                isset($post['delete_token']) &&
                $sessionToken !== null &&
                $sessionToken === $post['delete_token']
            ) {
                return $this->deleteFile($file);
            }
        }

        $deleteToken = uniqid('', true);
        $this->session->set('delete_token', $deleteToken);

        $pageTitle = htmlspecialchars($file->rus_name);
        $this->render->addData([
            'title'      => $pageTitle,
            'page_title' => $pageTitle,
        ]);

        return $this->render->render('downloads::delete_file', [
            'id'           => $id,
            'delete_token' => $deleteToken,
            'action_url'   => '/downloads/delete-file/' . $id . '/',
            'back_url'     => '/downloads/files/' . $id . '/',
        ]);
    }

    private function deleteFile(DownloadFile $file): string
    {
        $id = $file->id;

        foreach (Screen::getScreens($id) as $screen) {
            @unlink($screen['path']);
        }
        @rmdir(\UPLOAD_PATH . 'downloads' . \DS . 'screen' . \DS . $id);

        DownloadMoreFile::query()->where('refid', $id)->each(function (DownloadMoreFile $more) use ($file): void {
            if (is_file($file->dir . '/' . $more->name)) {
                @unlink($file->dir . '/' . $more->name);
            }
        });
        DownloadMoreFile::query()->where('refid', $id)->delete();

        DownloadBookmark::query()->where('file_id', $id)->delete();
        DownloadComment::query()->where('sub_id', $id)->delete();

        @unlink($file->dir . '/' . $file->name);

        $this->decrementCategoryCounters((int) $file->refid);

        DownloadFile::query()->where('id', $id)->delete();

        http_response_code(302);
        header('Location: /downloads/?id=' . $file->refid);
        exit;
    }

    private function decrementCategoryCounters(int $categoryId): void
    {
        $ids = [];
        $dirid = $categoryId;
        while ($dirid !== 0 && $dirid !== '') {
            $ids[] = $dirid;
            $cat = DownloadCategory::query()->select('refid')->find($dirid);
            if ($cat === null) {
                break;
            }
            $dirid = (int) $cat->refid;
        }

        if ($ids) {
            DownloadCategory::query()->whereIn('id', $ids)->decrement('total');
        }
    }
}
