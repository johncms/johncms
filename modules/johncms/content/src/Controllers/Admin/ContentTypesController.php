<?php

declare(strict_types=1);

namespace Johncms\Content\Controllers\Admin;

use Johncms\Content\Forms\ContentTypeForm;
use Johncms\Content\Models\ContentType;
use Johncms\Content\Resources\ContentTypeResource;
use Johncms\Content\Services\ContentTypeService;
use Johncms\Controller\BaseAdminController;
use Johncms\Exceptions\ValidationException;
use Johncms\Http\Request;
use Johncms\Http\Response\RedirectResponse;
use Johncms\Http\Session;

use const _PHPStan_39fe102d2\__;

class ContentTypesController extends BaseAdminController
{
    protected string $moduleName = 'johncms/content';

    public function __construct(
        private readonly Session $session
    ) {
        parent::__construct();
        $this->navChain->add(__('Content'), route('content.admin.index'));
        $this->metaTagManager->setAll(__('Content'));
    }

    public function index(): string
    {
        $contentTypes = ContentType::query()->get();

        return $this->render->render('johncms/content::admin/index', [
            'data' => [
                'message'      => $this->session->getFlash('message'),
                'contentTypes' => ContentTypeResource::createFromCollection($contentTypes)->toArray(),
            ],
        ]);
    }

    public function create(Request $request, ContentTypeForm $form): string | RedirectResponse
    {
        $this->metaTagManager->setAll(__('Create Content Type'));
        if ($request->isPost()) {
            try {
                $form->validate();
                $values = $form->getRequestValues();
                ContentType::query()->create($values);
                $this->session->flash('message', __('The Content Type was Successfully Created'));
                return new RedirectResponse(route('content.admin.index'));
            } catch (ValidationException $validationException) {
                return (new RedirectResponse(route('content.admin.type.create')))
                    ->withPost()
                    ->withValidationErrors($validationException->getErrors());
            }
        }

        return $this->render->render('johncms/content::admin/content_type_form', [
            'formTitle'        => __('Create Content Type'),
            'formFields'       => $form->getFormFields(),
            'validationErrors' => $form->getValidationErrors(),
            'storeUrl'         => route('content.admin.type.create'),
            'listUrl'          => route('content.admin.index'),
        ]);
    }

    public function edit(int $id, Request $request, ContentTypeForm $form): string | RedirectResponse
    {
        $contentType = ContentType::query()->findOrFail($id);
        $this->metaTagManager->setAll(__('Edit Content Type'));

        $form->setValues(
            [
                'name' => $contentType->name,
                'code' => $contentType->code,
            ]
        );

        if ($request->isPost()) {
            try {
                $form->validate();
                $values = $form->getRequestValues();
                $contentType->update($values);
                $this->session->flash('message', __('The Content Type was Successfully Updated'));
                return new RedirectResponse(route('content.admin.index'));
            } catch (ValidationException $validationException) {
                return (new RedirectResponse(route('content.admin.type.edit', ['id' => $id])))
                    ->withPost()
                    ->withValidationErrors($validationException->getErrors());
            }
        }

        return $this->render->render('johncms/content::admin/content_type_form', [
            'formTitle'        => __('Edit Content Type'),
            'formFields'       => $form->getFormFields(),
            'validationErrors' => $form->getValidationErrors(),
            'storeUrl'         => route('content.admin.type.edit', ['id' => $id]),
            'listUrl'          => route('content.admin.index'),
        ]);
    }

    public function delete(int $id, Request $request, ContentTypeService $contentTypeService): RedirectResponse | string
    {
        $data = [];
        $contentType = ContentType::query()->findOrFail($id);

        if ($request->isPost()) {
            $contentTypeService->delete($contentType);
            $this->session->flash('message', __('The Content Type was Successfully Deleted'));
            return new RedirectResponse(route('content.admin.index'));
        }

        $data['elementName'] = $contentType->name;
        $data['actionUrl'] = route('content.admin.type.delete', ['id' => $id]);

        return $this->render->render('johncms/content::admin/delete', ['data' => $data]);
    }
}
