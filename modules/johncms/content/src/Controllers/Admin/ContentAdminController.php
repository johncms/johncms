<?php

declare(strict_types=1);

namespace Johncms\Content\Controllers\Admin;

use Johncms\Content\Forms\ContentTypeForm;
use Johncms\Content\Models\ContentType;
use Johncms\Controller\BaseAdminController;
use Johncms\Exceptions\ValidationException;
use Johncms\Http\Request;
use Johncms\Http\Response\RedirectResponse;
use Johncms\Http\Session;

class ContentAdminController extends BaseAdminController
{
    protected string $moduleName = 'johncms/content';

    public function __construct()
    {
        parent::__construct();
        $this->navChain->add(__('Content'), route('content.admin.index'));
        $this->metaTagManager->setAll(__('Content'));
    }

    public function index(): string
    {
        return $this->render->render('johncms/content::admin/index', []);
    }

    public function createContentType(Request $request, Session $session, ContentTypeForm $form): string | RedirectResponse
    {
        if ($request->isPost()) {
            try {
                $form->validate();
                $values = $form->getRequestValues();
                ContentType::query()->create($values);
                $session->flash('message', __('The Content Type was Successfully Created'));
                return new RedirectResponse(route('content.admin.index'));
            } catch (ValidationException $validationException) {
                return (new RedirectResponse(route('content.admin.createContentType')))
                    ->withPost()
                    ->withValidationErrors($validationException->getErrors());
            }
        }

        return $this->render->render('johncms/content::admin/create_type_form', [
            'formFields'       => $form->getFormFields(),
            'validationErrors' => $form->getValidationErrors(),
            'storeUrl'         => route('content.admin.createContentType'),
            'listUrl'          => route('content.admin.index'),
        ]);
    }
}
