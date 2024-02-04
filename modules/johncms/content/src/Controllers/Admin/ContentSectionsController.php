<?php

declare(strict_types=1);

namespace Johncms\Content\Controllers\Admin;

use Illuminate\Database\Eloquent\Builder;
use Johncms\Content\Forms\ContentSectionForm;
use Johncms\Content\Models\ContentElement;
use Johncms\Content\Models\ContentSection;
use Johncms\Content\Models\ContentType;
use Johncms\Content\Resources\ContentElementResource;
use Johncms\Content\Resources\ContentSectionResource;
use Johncms\Content\Services\NavChainService;
use Johncms\Controller\BaseAdminController;
use Johncms\Exceptions\ValidationException;
use Johncms\Http\Request;
use Johncms\Http\Response\RedirectResponse;
use Johncms\Http\Session;

class ContentSectionsController extends BaseAdminController
{
    protected string $moduleName = 'johncms/content';

    public function __construct(
        private readonly NavChainService $breadcrumbs,
        private readonly Session $session
    ) {
        parent::__construct();
        $this->navChain->add(__('Content'), route('content.admin.index'));
        $this->metaTagManager->setAll(__('Content'));
    }

    public function index(int $type, ?int $sectionId): string
    {
        $contentType = ContentType::query()->findOrFail($type);
        if ($sectionId) {
            $contentSection = ContentSection::query()->findOrFail($sectionId);
            $this->metaTagManager->setAll($contentSection->name);
        } else {
            $this->metaTagManager->setAll($contentType->name);
        }

        $this->breadcrumbs->setAdminBreadcrumbs($type, $sectionId, true);

        $contentSections = ContentSection::query()
            ->when($sectionId > 0, function (Builder $query) use ($sectionId) {
                $query->where('parent', $sectionId);
            })
            ->when(! $sectionId, function (Builder $query) {
                $query->whereNull('parent');
            })
            ->where('content_type_id', $type)
            ->get();

        $contentElements = ContentElement::query()
            ->when($sectionId > 0, function (Builder $query) use ($sectionId) {
                $query->where('section_id', $sectionId);
            })
            ->when(! $sectionId, function (Builder $query) {
                $query->whereNull('section_id');
            })
            ->paginate();

        return $this->render->render('johncms/content::admin/sections', [
            'data' => [
                'typeId'           => $type,
                'sectionId'        => $sectionId,
                'createSectionUrl' => route('content.admin.sections.create', ['sectionId' => $sectionId, 'type' => $type]),
                'createElementUrl' => route('content.admin.elements.create', ['sectionId' => $sectionId, 'type' => $type]),
                'message'          => $this->session->getFlash('message'),
                'sections'         => ContentSectionResource::createFromCollection($contentSections)->toArray(),
                'elements'         => ContentElementResource::createFromCollection($contentElements)->toArray(),
                'pagination'       => $contentElements->render(),
            ],
        ]);
    }

    public function create(int $type, ?int $sectionId, Request $request, ContentSectionForm $form): string | RedirectResponse
    {
        $this->breadcrumbs->setAdminBreadcrumbs($type, $sectionId);
        $this->metaTagManager->setAll(__('Create Section'));
        $this->navChain->add(__('Create Section'));

        $form->setValues(
            [
                'content_type_id' => $type,
                'parent'          => $sectionId,
            ]
        );

        if ($request->isPost()) {
            try {
                $form->validate();
                $values = $form->getRequestValues();
                // TODO: Refactoring
                $values['content_type_id'] = $type;
                ContentSection::query()->create($values);
                $this->session->flash('message', __('The Section was Successfully Created'));
                return new RedirectResponse(route('content.admin.sections', ['type' => $type]));
            } catch (ValidationException $validationException) {
                return (new RedirectResponse(route('content.admin.sections.create', ['sectionId' => $sectionId, 'type' => $type])))
                    ->withPost()
                    ->withValidationErrors($validationException->getErrors());
            }
        }

        return $this->render->render('johncms/content::admin/content_section_form', [
            'formTitle'        => __('Create Section'),
            'formFields'       => $form->getFormFields(),
            'validationErrors' => $form->getValidationErrors(),
            'storeUrl'         => route('content.admin.sections.create', ['sectionId' => $sectionId, 'type' => $type]),
            'listUrl'          => route('content.admin.sections', ['sectionId' => $sectionId, 'type' => $type]),
        ]);
    }

    public function edit(int $id, Request $request, ContentSectionForm $form): string | RedirectResponse
    {
        $contentSection = ContentSection::query()->findOrFail($id);

        $this->breadcrumbs->setAdminBreadcrumbs($contentSection->content_type_id, $id);
        $this->metaTagManager->setAll($contentSection->name);
        $this->navChain->add($contentSection->name);

        $form->setValues(
            [
                'id'              => $contentSection->id,
                'name'            => $contentSection->name,
                'code'            => $contentSection->code,
                'content_type_id' => $contentSection->content_type_id,
                'parent'          => $contentSection->parent,
            ]
        );

        if ($request->isPost()) {
            try {
                $form->validate();
                $values = $form->getRequestValues();
                $contentSection->update($values);
                $this->session->flash('message', __('The Section was Successfully Updated'));
                return new RedirectResponse(route('content.admin.sections', ['type' => $contentSection->content_type_id]));
            } catch (ValidationException $validationException) {
                return (new RedirectResponse(route('content.admin.sections.edit', ['id' => $id])))
                    ->withPost()
                    ->withValidationErrors($validationException->getErrors());
            }
        }

        return $this->render->render('johncms/content::admin/content_section_form', [
            'formTitle'        => __('Edit Section'),
            'formFields'       => $form->getFormFields(),
            'validationErrors' => $form->getValidationErrors(),
            'storeUrl'         => route('content.admin.sections.edit', ['id' => $id]),
            'listUrl'          => route('content.admin.sections', ['sectionId' => $contentSection->parent, 'type' => $contentSection->content_type_id]),
        ]);
    }

    public function delete(int $type, int $id, Request $request): RedirectResponse | string
    {
        $data = [];
        $contentSection = ContentSection::query()->findOrFail($id);

        if ($request->isPost()) {
            $contentSection->delete();
            $this->session->flash('message', __('The Section was Successfully Deleted'));
            return new RedirectResponse(route('content.admin.sections', ['type' => $type]));
        }

        $data['elementName'] = $contentSection->name;
        $data['actionUrl'] = route('content.admin.sections.delete', ['id' => $id, 'type' => $type]);

        return $this->render->render('johncms/content::admin/delete', ['data' => $data]);
    }
}
