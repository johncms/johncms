<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\Controllers\Admin;

use Johncms\Auth\CurrentUser;
use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\Http\View\ViewResponse;
use Johncms\Modules\News\Application\DTO\ArticleFormDTO;
use Johncms\Modules\News\Application\Exceptions\ArticleFormException;
use Johncms\Modules\News\Application\Exceptions\ArticleNotFoundException;
use Johncms\Modules\News\Application\Services\ArticleFormMapper;
use Johncms\Modules\News\Application\Services\NewsListControlsMapper;
use Johncms\Modules\News\Application\UseCases\GetArticleUseCase;
use Johncms\Modules\News\Application\UseCases\GetSectionTreeUseCase;
use Johncms\Modules\News\Application\UseCases\SaveArticleUseCase;
use Johncms\Modules\News\Domain\Models\NewsArticle;
use Johncms\Modules\News\Domain\ValueObjects\NewsListFilter;
use Johncms\NavChain;

/**
 * The form of an article: adding one and changing one.
 *
 * Saving stays on the form of the article, which says so; "save and close" returns to the list of
 * its section.
 */
final readonly class ArticleFormController
{
    private const UPLOAD_URL = '/admin/news/upload_file';

    public function __construct(
        private NavChain $navChain,
        private GetArticleUseCase $getArticle,
        private GetSectionTreeUseCase $getTree,
        private SaveArticleUseCase $saveArticle,
        private ArticleFormMapper $mapper,
        private NewsListControlsMapper $controls,
        private Session $session,
    ) {
    }

    public function create(Request $request): ViewResponse
    {
        $section = $request->queryParam('section');

        return $this->renderCreate($this->mapper->blank(ctype_digit($section) ? (int) $section : NewsListFilter::ROOT), []);
    }

    public function store(Request $request, CurrentUser $user): ViewResponse
    {
        $form = $this->fromRequest($request);

        try {
            $article = $this->saveArticle->create($form, $user->id());
        } catch (ArticleFormException $e) {
            return $this->renderCreate($form, $e->errors);
        }

        $this->session->flash('success_message', __('The article was created successfully'));
        redirect($this->afterSave($request, $article->id, (int) $article->section_id));
    }

    public function edit(int $id): ViewResponse
    {
        $article = $this->article($id);

        return $this->renderEdit($article, $this->mapper->fromArticle($article), []);
    }

    public function update(Request $request, CurrentUser $user, int $id): ViewResponse
    {
        $article = $this->article($id);
        $form = $this->fromRequest($request);

        try {
            $this->saveArticle->update($article, $form, $user->id());
        } catch (ArticleFormException $e) {
            return $this->renderEdit($article, $form, $e->errors);
        }

        $this->session->flash('success_message', __('The article was updated successfully'));
        redirect($this->afterSave($request, $article->id, $form->sectionId));
    }

    private function article(int $id): NewsArticle
    {
        try {
            return $this->getArticle->execute($id);
        } catch (ArticleNotFoundException) {
            pageNotFound('@admin/pages/errors/404.twig');
        }
    }

    private function fromRequest(Request $request): ArticleFormDTO
    {
        return new ArticleFormDTO(
            active: $request->bodyInt('active') === 1,
            sectionId: $request->bodyInt('section_id'),
            name: $request->body('name'),
            code: $request->body('code'),
            pageTitle: $request->body('page_title'),
            activeFrom: $request->body('active_from'),
            activeTo: $request->body('active_to'),
            keywords: $request->body('keywords'),
            description: $request->body('description'),
            tags: $request->body('tags'),
            previewText: $request->body('preview_text'),
            text: $request->body('text'),
            attachedFiles: array_values((array) $request->bodyInts('attached_files')),
        );
    }

    private function afterSave(Request $request, int $articleId, int $sectionId): string
    {
        return $request->hasBody('close') ? $this->listUrl($sectionId) : '/admin/news/articles/' . $articleId . '/edit';
    }

    private function listUrl(int $sectionId): string
    {
        return $this->controls->url($sectionId !== NewsListFilter::ROOT ? new NewsListFilter(section: $sectionId) : new NewsListFilter());
    }

    /**
     * @param array<string, list<string>> $errors
     */
    private function renderCreate(ArticleFormDTO $form, array $errors): ViewResponse
    {
        $title = __('Add article');
        $this->navChain->add(__('News'), NewsListControlsMapper::PATH);
        $this->navChain->add($title);

        return $this->render($title, $form, $errors, [
            'form_action' => '/admin/news/articles/new',
            'site_url'    => '',
            'facts'       => null,
        ]);
    }

    /**
     * @param array<string, list<string>> $errors
     */
    private function renderEdit(NewsArticle $article, ArticleFormDTO $form, array $errors): ViewResponse
    {
        $this->navChain->add(__('News'), NewsListControlsMapper::PATH);
        $this->navChain->add($article->name);

        return $this->render(__('Edit article'), $form, $errors, [
            'form_action' => '/admin/news/articles/' . $article->id . '/edit',
            'site_url'    => $article->url,
            'facts'       => $this->mapper->facts($article),
        ]);
    }

    /**
     * @param array<string, list<string>> $errors
     * @param array<string, mixed>        $data
     */
    private function render(string $title, ArticleFormDTO $form, array $errors, array $data): ViewResponse
    {
        return new ViewResponse('@news/admin/article-form.twig', $data + [
            'title'           => $title,
            'page_title'      => $title,
            'module_menu'     => ['news' => true],
            'fields'          => $this->mapper->fields($form),
            'errors'          => $errors,
            'sections'        => $this->mapper->sectionOptions($this->getTree->execute()),
            'status'          => $this->mapper->status($form),
            'list_url'        => $this->listUrl($form->sectionId),
            'upload_url'      => self::UPLOAD_URL,
            'success_message' => (string) $this->session->getFlash('success_message'),
        ]);
    }
}
