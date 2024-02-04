<?php

declare(strict_types=1);

namespace Johncms\Content\Forms;

use Johncms\Content\Services\ContentSectionService;
use Johncms\Forms\AbstractForm;
use Johncms\Forms\Inputs\InputHidden;
use Johncms\Forms\Inputs\InputText;
use Johncms\Forms\Inputs\Select;
use Johncms\Http\Request;

class ContentSectionForm extends AbstractForm
{
    public function __construct(
        private readonly ContentSectionService $contentSectionService,
        Request $request,
        array $values = []
    ) {
        parent::__construct($request, $values);
    }

    protected function prepareFormFields(): array
    {
        $fields = [];

        $fields['content_type_id'] = (new InputHidden())
            ->setNameAndId('content_type_id')
            ->setValue($this->getValue('content_type_id'));

        $fields['parent'] = (new Select())
            ->setLabel(__('Parent Section'))
            ->setNameAndId('parent')
            ->setPlaceholder('test')
            ->setValue($this->getValue('parent'))
            ->setOptions($this->getSections());

        $fields['name'] = (new InputText())
            ->setLabel(__('Name'))
            ->setPlaceholder(p__('placeholder', 'Enter the Name of the Section'))
            ->setNameAndId('name')
            ->setValue($this->getValue('name'))
            ->setValidationRules(['NotEmpty']);

        $fields['code'] = (new InputText())
            ->setLabel(__('Code'))
            ->setPlaceholder(p__('placeholder', 'Enter the Code of the Section'))
            ->setNameAndId('code')
            ->setValue($this->getValue('code'))
            ->setValidationRules(['NotEmpty']);

        return $fields;
    }

    private function getSections(): array
    {
        $result = [
            [
                'name'  => __('Root'),
                'value' => null,
            ],
        ];
        $contentTypeId = (int) $this->getValue('content_type_id');
        $sections = $this->contentSectionService->getAllContentTypeSectionsFlatList($contentTypeId, [$this->getValue('id')]);

        foreach ($sections as $section) {
            $result[] = [
                'name'  => str_repeat('&bull;', $section['level'] + 1) . ' ' . $section['name'],
                'value' => $section['id'],
            ];
        }

        return $result;
    }

    public function getRequestValues(): array
    {
        $result = parent::getRequestValues();
        if (empty($result['parent'])) {
            $result['parent'] = null;
        }
        return $result;
    }
}
