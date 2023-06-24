<?php

declare(strict_types=1);

namespace Johncms\Content\Forms;

use Johncms\Forms\AbstractForm;
use Johncms\Forms\Inputs\InputText;

class ContentTypeForm extends AbstractForm
{
    protected function prepareFormFields(): array
    {
        $fields = [];
        $fields['name'] = (new InputText())
            ->setLabel(__('Name'))
            ->setPlaceholder(p__('placeholder', 'Enter the Name of the Content Type'))
            ->setNameAndId('name')
            ->setValue($this->getValue('name'))
            ->setValidationRules(['NotEmpty']);

        $fields['code'] = (new InputText())
            ->setLabel(__('Code'))
            ->setPlaceholder(p__('placeholder', 'Enter the Code of the Content Type'))
            ->setNameAndId('code')
            ->setValidationRules(['NotEmpty']);

        return $fields;
    }
}
