<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\Controllers\Admin;

use Johncms\Auth\CurrentUser;
use Johncms\Http\EditorImageUploadResponder;
use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\Http\View\ViewResponse;
use Johncms\Modules\News\Application\DTO\ArticleFormDTO;
use Johncms\Modules\News\Application\Exceptions\ArticleFormException;
use Johncms\Modules\News\Application\Exceptions\ArticleNotFoundException;
use Johncms\Modules\News\Application\UseCases\GetArticleUseCase;
use Johncms\Modules\News\Application\UseCases\GetSectionTreeUseCase;
use Johncms\Modules\News\Application\UseCases\SaveArticleUseCase;
use Johncms\Modules\News\Application\Utils\Helpers;
use Johncms\Modules\News\Domain\Models\NewsArticle;
use Johncms\NavChain;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

final readonly class AdminArticleController
{
    public function __construct(
        private NavChain $navChain,
        private GetArticleUseCase $getArticle,
        private GetSectionTreeUseCase $getTree,
        private SaveArticleUseCase $saveArticle,
        private Session $session,
    ) {
    }

    /**
     * Adding an article to the section of the address.
     */
    public function add(Request $request, CurrentUser $user, int $section_id = 0): Response | ViewResponse
    {
        if ($section_id !== 0 && ! $this->getTree->execute()->has($section_id)) {
            pageNotFound();
        }

        $form = $this->form($request, $section_id);
        $errors = [];

        if ($request->getMethod() === 'POST') {
            try {
                $this->saveArticle->create($form, $user->id());
                $this->session->flash('success_message', __('The article was created successfully'));

                return new RedirectResponse('/admin/news/content/' . $section_id);
            } catch (ArticleFormException $e) {
                $errors = $e->errors;
            }
        }

        $this->navChain->add(__('News'), '/admin/news');
        $this->navChain->add(__('Add article'));

        return new ViewResponse('@news/admin/article-form.twig', $this->menu(__('Add article')) + [
            'action_url' => '/admin/news/add_article/' . $section_id,
            'back_url'   => '/admin/news/content/' . $section_id,
            'section_id' => $section_id,
            'fields'     => $this->fields($form),
            'errors'     => $this->flatten($errors),
        ]);
    }

    /**
     * Changing an article; the section it stands in stays.
     */
    public function edit(int $article_id, Request $request, CurrentUser $user): Response | ViewResponse
    {
        try {
            $article = $this->getArticle->execute($article_id);
        } catch (ArticleNotFoundException) {
            pageNotFound();
        }

        $sectionId = (int) $article->section_id;
        $form = $request->getMethod() === 'POST' ? $this->form($request, $sectionId) : $this->formOf($article);
        $errors = [];

        if ($request->getMethod() === 'POST') {
            try {
                $this->saveArticle->update($article, $form, $user->id());
                $this->session->flash('success_message', __('The article was updated successfully'));

                return new RedirectResponse('/admin/news/content/' . $sectionId . '/');
            } catch (ArticleFormException $e) {
                $errors = $e->errors;
            }
        }

        $this->navChain->add(__('News'), '/admin/news');
        Helpers::buildAdminBreadcrumbs($article->parentSection);
        $this->navChain->add($article->name);

        return new ViewResponse('@news/admin/article-form.twig', $this->menu(__('Edit article')) + [
            'action_url' => '/admin/news/edit_article/' . $article->id . '/',
            'back_url'   => '/admin/news/content/' . $sectionId . '/',
            'article_id' => $article->id,
            'fields'     => $this->fields($form),
            'errors'     => $this->flatten($errors),
        ]);
    }

    public function loadFile(Request $request, EditorImageUploadResponder $responder): JsonResponse
    {
        return $responder->store($request, 'news', 'news');
    }

    private function form(Request $request, int $sectionId): ArticleFormDTO
    {
        return new ArticleFormDTO(
            active: $request->bodyInt('active', 1) === 1,
            sectionId: $sectionId,
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

    private function formOf(NewsArticle $article): ArticleFormDTO
    {
        return new ArticleFormDTO(
            active: (bool) $article->active,
            sectionId: (int) $article->section_id,
            name: $article->name,
            code: $article->code,
            pageTitle: (string) $article->page_title,
            activeFrom: $this->date($article->getRawOriginal('active_from')),
            activeTo: $this->date($article->getRawOriginal('active_to')),
            keywords: (string) $article->keywords,
            description: (string) $article->description,
            tags: implode(', ', $article->tags),
            previewText: (string) $article->getRawOriginal('preview_text'),
            text: (string) $article->getRawOriginal('text'),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function fields(ArticleFormDTO $form): array
    {
        return [
            'active'       => (int) $form->active,
            'section_id'   => $form->sectionId,
            'active_from'  => $form->activeFrom,
            'active_to'    => $form->activeTo,
            'name'         => $form->name,
            'page_title'   => $form->pageTitle,
            'code'         => $form->code,
            'keywords'     => $form->keywords,
            'description'  => $form->description,
            'tags'         => $form->tags,
            'preview_text' => $form->previewText,
            'text'         => $form->text,
        ];
    }

    private function date(mixed $raw): string
    {
        $timestamp = is_string($raw) && $raw !== '' ? strtotime($raw) : false;

        return $timestamp !== false ? date(SaveArticleUseCase::DATE_FORMAT, $timestamp) : '';
    }

    /**
     * @param array<string, list<string>> $errors
     * @return list<string>
     */
    private function flatten(array $errors): array
    {
        return array_merge(...array_values($errors));
    }

    /**
     * @return array<string, mixed>
     */
    private function menu(string $title): array
    {
        return [
            'title'       => $title,
            'page_title'  => $title,
            'module_menu' => ['news' => true],
        ];
    }
}
