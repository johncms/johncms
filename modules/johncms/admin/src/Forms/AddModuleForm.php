<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Admin\Forms;

use Johncms\Forms\AbstractForm;
use Johncms\Forms\Inputs\InputText;

class AddModuleForm extends AbstractForm
{
    protected function prepareFormFields(): array
    {
        $fields = [];
        $fields['name'] = (new InputText())
            ->setLabel(__('Module Name'))
            ->setPlaceholder(__('Enter module name'))
            ->setNameAndId('name')
            ->setValue($this->getValue('name'))
            ->setHelpText(__('For example: johncms/contacts'))
            ->setValidationRules(['NotEmpty']);

        return $fields;
    }
}
