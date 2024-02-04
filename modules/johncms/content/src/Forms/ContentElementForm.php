<?php

declare(strict_types=1);

namespace Johncms\Content\Forms;

use Johncms\Forms\AbstractForm;
use Johncms\Forms\Inputs\InputText;
use Johncms\Forms\Inputs\Textarea;

class ContentElementForm extends AbstractForm
{
    protected function prepareFormFields(): array
    {
        $fields = [];
        $fields['name'] = (new InputText())
            ->setLabel(__('Name'))
            ->setPlaceholder(p__('placeholder', 'Enter the Name of the Element'))
            ->setNameAndId('name')
            ->setValue($this->getValue('name'))
            ->setValidationRules(['NotEmpty']);

        $fields['code'] = (new InputText())
            ->setLabel(__('Code'))
            ->setPlaceholder(p__('placeholder', 'Enter the Code of the Element'))
            ->setNameAndId('code')
            ->setValue($this->getValue('code'))
            ->setValidationRules(['NotEmpty']);

        $fields['detail_text'] = (new Textarea())
            ->setLabel(__('Detail Text'))
            ->setPlaceholder(p__('placeholder', 'Enter the Detail Text'))
            ->setNameAndId('detail_text')
            ->setValue($this->getValue('detail_text'));

        return $fields;
    }
}
