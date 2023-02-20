<?php

declare(strict_types=1);

namespace Johncms\Admin\Controllers\Modules;

use Johncms\Admin\Forms\AddModuleForm;
use Johncms\Controller\BaseAdminController;
use Johncms\Exceptions\ValidationException;
use Johncms\Http\Request;
use Johncms\Http\Response\RedirectResponse;
use Johncms\Modules\ComposerModuleInstaller;
use Johncms\Modules\Modules;
use Psr\Http\Message\ResponseInterface;

class ModulesController extends BaseAdminController
{
    protected string $moduleName = 'johncms/admin';

    public function index(): string
    {
        $this->metaTagManager->setAll(__('List of Modules'));
        $this->navChain->add(__('List of Modules'));
        return $this->render->render('johncms/admin::modules/index', [
            'data' => [
                'modules' => Modules::getModulesWithMetaData(),
            ],
        ]);
    }

    /**
     * Add module page
     */
    public function add(Request $request, AddModuleForm $installModuleForm, ComposerModuleInstaller $moduleInstaller): ResponseInterface | string
    {
        $this->navChain->add(__('List of Modules'), route('admin.modules'));
        $this->navChain->add(__('Add Module'));

        // If the form is submitted, install the module.
        if ($request->isPost()) {
            try {
                $installModuleForm->validate();
                $formValues = $installModuleForm->getRequestValues();

                // Install module
                $installResult = $moduleInstaller->install($formValues['name']);

                return $this->render->render('admin::modules/install_result', [
                    'data' => [
                        'result' => $installResult['success'],
                        'log'    => $installResult['output'],
                    ],
                ]);
            } catch (ValidationException $validationException) {
                return (new RedirectResponse(route('admin.modules.add')))
                    ->withPost()
                    ->withValidationErrors($validationException->getErrors());
            }
        }

        $this->metaTagManager->setAll(__('Add Module'));
        return $this->render->render('johncms/admin::modules/add', [
            'data' => [
                'formFields'       => $installModuleForm->getFormFields(),
                'validationErrors' => $installModuleForm->getValidationErrors(),
                'storeUrl'         => route('admin.modules.add'),
            ],
        ]);
    }

    /**
     * Delete module page
     */
    public function delete(Request $request, ComposerModuleInstaller $moduleInstaller): string | RedirectResponse
    {
        $this->navChain->add(__('List of Modules'), route('admin.modules'));
        $this->navChain->add(__('Delete Module'));

        $moduleName = $request->getQuery('name', '');
        if (empty($moduleName)) {
            return (new RedirectResponse(route('admin.modules')));
        }

        if ($request->isPost()) {
            $result = $moduleInstaller->remove($moduleName);

            return $this->render->render('johncms/admin::modules/delete_result', [
                'data' => [
                    'result' => $result['success'],
                    'log'    => $result['output'],
                ],
            ]);
        }

        $this->metaTagManager->setAll(__('Delete Module'));
        return $this->render->render('johncms/admin::modules/delete', [
            'data' => [
                'name'     => htmlspecialchars($request->getQuery('name')),
                'storeUrl' => route('admin.modules.delete', queryParams: ['name' => $moduleName]),
            ],
        ]);
    }

    /**
     * Delete module page
     */
    public function update(Request $request, ComposerModuleInstaller $moduleInstaller): string | RedirectResponse
    {
        $this->navChain->add(__('List of Modules'), route('admin.modules'));
        $this->navChain->add(__('Update Module'));

        $moduleName = $request->getQuery('name', '');
        if (empty($moduleName)) {
            return (new RedirectResponse(route('admin.modules')));
        }

        if ($request->isPost()) {
            $result = $moduleInstaller->update($moduleName);

            return $this->render->render('johncms/admin::modules/update_result', [
                'data' => [
                    'result' => $result['success'],
                    'log'    => $result['output'],
                ],
            ]);
        }

        $this->metaTagManager->setAll(__('Update Module'));
        return $this->render->render('johncms/admin::modules/update', [
            'data' => [
                'name'     => htmlspecialchars($request->getQuery('name')),
                'storeUrl' => route('admin.modules.update', queryParams: ['name' => $moduleName]),
            ],
        ]);
    }
}
