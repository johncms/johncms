<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers\Admin;

use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\Http\View\ViewResponse;
use Johncms\Modules\Forum\Application\DTO\SectionFormDTO;
use Johncms\Modules\Forum\Application\Exceptions\SectionFormException;
use Johncms\Modules\Forum\Application\Services\SectionFormMapper;
use Johncms\Modules\Forum\Application\UseCases\SaveForumSectionUseCase;
use Johncms\Modules\Forum\Domain\Models\ForumSection;
use Johncms\Modules\Forum\Domain\Repository\ForumStructureRepositoryInterface;
use Johncms\NavChain;

/**
 * The form of a section of the forum: adding one and changing one, its place in the tree among the
 * rest.
 *
 * Saving stays on the form of the section, which says so; "save and close" returns to the tree.
 */
final readonly class SectionFormController
{
    private const string LIST_URL = '/admin/forum';

    public function __construct(
        private NavChain $navChain,
        private ForumStructureRepositoryInterface $repository,
        private SaveForumSectionUseCase $saveSection,
        private SectionFormMapper $mapper,
        private Session $session,
    ) {
    }

    public function create(Request $request): ViewResponse
    {
        $parent = $request->queryInt('parent');

        return $this->renderCreate(new SectionFormDTO($parent, ''), []);
    }

    public function store(Request $request): ViewResponse
    {
        $form = $this->fromRequest($request);

        try {
            $section = $this->saveSection->create($form);
        } catch (SectionFormException $e) {
            return $this->renderCreate($form, $e->errors);
        }

        $this->session->flash('success_message', __('The section was created'));
        redirect($this->afterSave($request, $section->id));
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

        $this->session->flash('success_message', __('The section was saved'));
        redirect($this->afterSave($request, $section->id));
    }

    private function section(int $id): ForumSection
    {
        return $this->repository->find($id) ?? pageNotFound('@admin/pages/errors/404.twig');
    }

    private function fromRequest(Request $request): SectionFormDTO
    {
        $sort = trim($request->body('sort'));

        return new SectionFormDTO(
            parentId: $request->bodyInt('parent'),
            name: $request->body('name'),
            description: $request->body('description'),
            sort: ctype_digit($sort) ? (int) $sort : null,
            sectionType: $request->bodyInt('section_type'),
            access: $request->bodyInt('access'),
            metaKeywords: $request->body('meta_keywords'),
            metaDescription: $request->body('meta_description'),
        );
    }

    private function afterSave(Request $request, int $sectionId): string
    {
        return $request->hasBody('close') ? self::LIST_URL : '/admin/forum/sections/' . $sectionId . '/edit';
    }

    /**
     * @param array<string, list<string>> $errors
     */
    private function renderCreate(SectionFormDTO $form, array $errors): ViewResponse
    {
        $title = $form->parentId > 0 ? __('Add Section') : __('Add Category');
        $this->navChain->add(__('Forum'), self::LIST_URL);
        $this->navChain->add($title);

        return $this->render($title, $form, $errors, null, [
            'form_action' => '/admin/forum/sections/new',
            'site_url'    => '',
            'facts'       => null,
        ]);
    }

    /**
     * @param array<string, list<string>> $errors
     */
    private function renderEdit(ForumSection $section, SectionFormDTO $form, array $errors): ViewResponse
    {
        $this->navChain->add(__('Forum'), self::LIST_URL);
        $this->navChain->add($section->name);

        return $this->render(__('Edit Section'), $form, $errors, $section->id, [
            'form_action' => '/admin/forum/sections/' . $section->id . '/edit',
            'site_url'    => $section->url,
            'facts'       => $this->mapper->facts(
                $section,
                $this->repository->countChildSections($section->id),
                $this->repository->countTopics($section->id),
            ),
        ]);
    }

    /**
     * @param array<string, list<string>> $errors
     * @param array<string, mixed>        $data
     */
    private function render(string $title, SectionFormDTO $form, array $errors, ?int $sectionId, array $data): ViewResponse
    {
        return new ViewResponse('@forum/admin/section-form.twig', $data + [
            'title'           => $title,
            'page_title'      => $title,
            'module_menu'     => ['forum' => true],
            'fields'          => $this->mapper->fields($form),
            'errors'          => $errors,
            'parents'         => $this->mapper->parentOptions($this->repository->tree(), $sectionId),
            'type_options'    => $this->mapper->typeOptions(),
            'access_options'  => $this->mapper->accessOptions(),
            'list_url'        => self::LIST_URL,
            'success_message' => (string) $this->session->getFlash('success_message'),
        ]);
    }
}
