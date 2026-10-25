<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\Controllers\Admin;

use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\Http\View\ViewResponse;
use Johncms\Modules\News\Application\DTO\SectionFormDTO;
use Johncms\Modules\News\Application\Exceptions\SectionFormException;
use Johncms\Modules\News\Application\Exceptions\SectionNotFoundException;
use Johncms\Modules\News\Application\Services\NewsListControlsMapper;
use Johncms\Modules\News\Application\Services\SectionFormMapper;
use Johncms\Modules\News\Application\UseCases\GetSectionTreeUseCase;
use Johncms\Modules\News\Application\UseCases\GetSectionUseCase;
use Johncms\Modules\News\Application\UseCases\SaveSectionUseCase;
use Johncms\Modules\News\Domain\Enums\NewsListTab;
use Johncms\Modules\News\Domain\Models\NewsSection;
use Johncms\Modules\News\Domain\ValueObjects\NewsListFilter;
use Johncms\NavChain;

/**
 * The form of a section: adding one and changing one, the place in the tree among the rest.
 *
 * Saving stays on the form of the section, which says so; "save and close" returns to the sections
 * beside it.
 */
final readonly class SectionFormController
{
    public function __construct(
        private NavChain $navChain,
        private GetSectionUseCase $getSection,
        private GetSectionTreeUseCase $getTree,
        private SaveSectionUseCase $saveSection,
        private SectionFormMapper $mapper,
        private NewsListControlsMapper $controls,
        private Session $session,
    ) {
    }

    public function create(Request $request): ViewResponse
    {
        $parent = $request->queryParam('parent');
        $form = new SectionFormDTO(ctype_digit($parent) ? (int) $parent : NewsListFilter::ROOT, '', '');

        return $this->renderCreate($form, []);
    }

    public function store(Request $request): ViewResponse
    {
        $form = $this->fromRequest($request);

        try {
            $section = $this->saveSection->create($form);
        } catch (SectionFormException $e) {
            return $this->renderCreate($form, $e->errors);
        }

        $this->session->flash('success_message', __('The section was created successfully'));
        redirect($this->afterSave($request, $section->id, $form->parentId));
    }

    public function edit(int $id): ViewResponse
    {
        $section = $this->section($id);

        return $this->renderEdit($section, $this->mapper->fromSection($section), []);
    }

    public function update(Request $request, int $id): ViewResponse
    {
        $section = $this->section($id);
        $form = $this->fromRequest($request);

        try {
            $this->saveSection->update($section, $form);
        } catch (SectionFormException $e) {
            return $this->renderEdit($section, $form, $e->errors);
        }

        $this->session->flash('success_message', __('The section was updated successfully'));
        redirect($this->afterSave($request, $section->id, $form->parentId));
    }

    private function section(int $id): NewsSection
    {
        try {
            return $this->getSection->execute($id);
        } catch (SectionNotFoundException) {
            pageNotFound('@admin/pages/errors/404.twig');
        }
    }

    private function fromRequest(Request $request): SectionFormDTO
    {
        return new SectionFormDTO(
            parentId: $request->bodyInt('parent'),
            name: $request->body('name'),
            code: $request->body('code'),
            keywords: $request->body('keywords'),
            description: $request->body('description'),
            text: $request->body('text'),
        );
    }

    private function afterSave(Request $request, int $sectionId, int $parentId): string
    {
        return $request->hasBody('close') ? $this->listUrl($parentId) : '/admin/news/sections/' . $sectionId . '/edit';
    }

    /**
     * The sections beside it: under the same parent, or all of them for the root.
     */
    private function listUrl(int $parentId): string
    {
        return $this->controls->url(new NewsListFilter(
            tab: NewsListTab::SECTIONS,
            section: $parentId !== NewsListFilter::ROOT ? $parentId : null,
        ));
    }

    /**
     * @param array<string, list<string>> $errors
     */
    private function renderCreate(SectionFormDTO $form, array $errors): ViewResponse
    {
        $title = __('Create section');
        $this->navChain->add(__('News'), NewsListControlsMapper::PATH);
        $this->navChain->add($title);

        return $this->render($title, $form, $errors, null, [
            'form_action'  => '/admin/news/sections/new',
            'site_url'     => '',
            'articles_url' => '',
            'facts'        => null,
        ]);
    }

    /**
     * @param array<string, list<string>> $errors
     */
    private function renderEdit(NewsSection $section, SectionFormDTO $form, array $errors): ViewResponse
    {
        $this->navChain->add(__('News'), NewsListControlsMapper::PATH);
        $this->navChain->add($section->name);

        return $this->render(__('Edit section'), $form, $errors, $section->id, [
            'form_action'  => '/admin/news/sections/' . $section->id . '/edit',
            'site_url'     => $section->url,
            'articles_url' => $this->controls->url(new NewsListFilter(section: $section->id)),
            'facts'        => $this->mapper->facts($section),
        ]);
    }

    /**
     * @param array<string, list<string>> $errors
     * @param array<string, mixed>        $data
     */
    private function render(string $title, SectionFormDTO $form, array $errors, ?int $sectionId, array $data): ViewResponse
    {
        return new ViewResponse('@news/admin/section-form.twig', $data + [
            'title'           => $title,
            'page_title'      => $title,
            'module_menu'     => ['news' => true],
            'fields'          => $this->mapper->fields($form),
            'errors'          => $errors,
            'parents'         => $this->mapper->parentOptions($this->getTree->execute(), $sectionId),
            'list_url'        => $this->listUrl($form->parentId),
            'success_message' => (string) $this->session->getFlash('success_message'),
        ]);
    }
}
