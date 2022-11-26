<?php

declare(strict_types=1);

namespace Johncms\Admin\Controllers\Modules;

use Johncms\Admin\Forms\AddModuleForm;
use Johncms\Controller\BaseAdminController;
use Johncms\Exceptions\ValidationException;
use Johncms\Http\Request;
use Johncms\Http\Response\RedirectResponse;
use Johncms\Http\Session;
use Johncms\Modules\ComposerModuleInstaller;
use Johncms\Modules\Modules;
use Psr\Http\Message\ResponseInterface;

class ModulesController extends BaseAdminController
{
    protected string $moduleName = 'johncms/admin';

    public function index(): string
    {
        $this->metaTagManager->setAll(__('List of Modules'));
        return $this->render->render('admin::modules/index', [
            'data' => [
                'modules' => Modules::getModulesWithMetaData(),
            ],
        ]);
    }

    public function add(Request $request, Session $session, AddModuleForm $installModuleForm, ComposerModuleInstaller $moduleInstaller): ResponseInterface | string
    {
        // If the form is submitted, install the module.
        if ($request->isPost()) {
            try {
                $installModuleForm->validate();
                $formValues = $installModuleForm->getRequestValues();

                // Install module
                $installResult = $moduleInstaller->install($formValues['name']);

                return $this->render->render('admin::modules/install_result', [
                    'data' => [
                        'installResult' => $installResult['success'],
                        'installLog'    => $installResult['output'],
                    ],
                ]);
            } catch (ValidationException $validationException) {
                return (new RedirectResponse(route('admin.modules.add')))
                    ->withPost()
                    ->withValidationErrors($validationException->getErrors());
            }
        }

        $this->metaTagManager->setAll(__('Add Module'));
        return $this->render->render('admin::modules/add', [
            'data' => [
                'formFields'       => $installModuleForm->getFormFields(),
                'validationErrors' => $installModuleForm->getValidationErrors(),
                'storeUrl'         => route('admin.modules.add'),
                'authError'        => $session->getFlash('authError'),
            ],
        ]);
    }
}
