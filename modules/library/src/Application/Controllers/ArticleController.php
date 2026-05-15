<?php

declare(strict_types=1);

namespace Johncms\Modules\Library\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Http\PageMeta;
use Johncms\Modules\Library\Domain\Models\LibraryText;
use Johncms\NavChain;
use Johncms\System\Http\Request;
use Johncms\System\Legacy\Tools;
use Johncms\System\View\Render;
use Johncms\Users\User;
use Library\Hashtags;
use Library\Rating;
use Library\Tree;
use Library\Utils;
use PDO;

final readonly class ArticleController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private NavChain $navChain,
        private Request $request,
        private Tools $tools,
        private User $currentUser,
        private PDO $db,
    ) {
        $this->controllerContext->initModule('library');
    }

    public function __invoke(int $id): string
    {
        $article = LibraryText::query()->find($id);

        if ($article === null || (! $article->premod && ! ($this->currentUser->rights > 4))) {
            http_response_code(404);
            return $this->render->render('system::pages/result', [
                'title'   => __('Library'),
                'type'    => 'alert-danger',
                'message' => __('Articles do not exist'),
            ]);
        }

        if (! isset($_SESSION['lib']) || $_SESSION['lib'] !== $id) {
            $_SESSION['lib'] = $id;
            $article->increment('count_views');
        }

        $symbols    = 7000;
        $textLength = (int) $this->db->query("SELECT CHAR_LENGTH(`text`) FROM `library_texts` WHERE `id` = $id LIMIT 1")->fetchColumn();
        $countPages = max(1, (int) ceil($textLength / $symbols));
        $page       = max(1, min((int) $this->request->getQuery('page', 1), $countPages));

        $offset  = $page === 1 ? 1 : ($page - 1) * $symbols;
        $rawText = (string) $this->db->query("SELECT SUBSTRING(`text`, $offset, " . ($symbols + 100) . ") FROM `library_texts` WHERE `id` = $id")->fetchColumn();
        $tmp     = mb_substr($rawText, $symbols, 100);

        $start  = $page === 1 ? 0 : min(Utils::position($rawText, PHP_EOL), Utils::position($rawText, ' '));
        $length = ($countPages === 1 || $page === $countPages)
            ? $symbols
            : $symbols + min(Utils::position($tmp, PHP_EOL), Utils::position($tmp, ' ')) - $start;

        $text = $this->tools->checkout(mb_substr($rawText, $start, $length), 1, 1);
        $text = $this->tools->smilies($text, $this->currentUser->rights ? 1 : 0);

        $isAdmin   = $this->currentUser->rights > 4;
        $moderMenu = $isAdmin || ($this->currentUser->isValid() && (int) $article->uploader_id === (int) $this->currentUser->id);

        $this->navChain->add(__('Library'), '/library/');
        $dirNav = new Tree($article->cat_id);
        $dirNav->processNavPanel();
        $dirNav->printNavPanel();
        $this->navChain->add($this->tools->checkout($article->name));

        $pageTitle = $this->tools->checkout($article->name);
        $meta      = new PageMeta($pageTitle . ' — ' . __('Library'), $page);
        $this->render->addData([
            'title'      => $meta->title,
            'page_title' => $pageTitle,
        ]);

        $tags        = null;
        $who         = null;
        $ratingVote  = null;
        $ratingView  = null;
        $cover       = null;

        if ($page === 1) {
            $tags = (new Hashtags($id))->getAllStatTags(1) ?: null;

            $rate       = new Rating($id);
            $ratingVote = $this->currentUser->isValid() ? $rate->printVote() : null;
            $ratingView = $rate->viewRate(1);

            $uploader = $article->uploader_id
                ? '<a href="' . config('johncms')['homeurl'] . '/profile/?user=' . $article->uploader_id . '">' . $this->tools->checkout($article->uploader) . '</a>'
                : $this->tools->checkout($article->uploader);
            $who = $uploader . ' (' . $this->tools->displayDate($article->time) . ')';

            $cover = file_exists(UPLOAD_PATH . 'library/images/big/' . $id . '.png');
        }

        return $this->render->render('library::book', [
            'res'         => [
                'id'          => $article->id,
                'text'        => $text,
                'name'        => $pageTitle,
                'count_views' => $article->count_views,
                'comm_count'  => $article->comm_count,
                'comments'    => $article->comments,
            ],
            'page'        => $page,
            'count_pages' => $countPages,
            'tags'        => $tags,
            'who'         => $who,
            'ratingVote'  => $ratingVote,
            'ratingView'  => $ratingView,
            'cover'       => $cover,
            'moderMenu'   => $moderMenu,
            'pagination'  => $this->tools->displayPagination('/library/article/' . $id . '?', $page - 1, $countPages, 1),
        ]);
    }
}
