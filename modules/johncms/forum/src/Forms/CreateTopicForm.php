<?php

declare(strict_types=1);

namespace Johncms\Forum\Forms;

use Illuminate\Database\Eloquent\Builder;
use Johncms\Forms\AbstractForm;
use Johncms\Forms\Inputs\Checkbox;
use Johncms\Forms\Inputs\InputText;
use Johncms\Forms\Inputs\Textarea;
use Johncms\Forum\Models\ForumTopic;
use Johncms\Users\User;

class CreateTopicForm extends AbstractForm
{
    private int $sectionId = 0;

    public function setSectionId(int $id): self
    {
        $this->sectionId = $id;
        return $this;
    }

    protected function prepareFormFields(): array
    {
        $user = di(User::class);
        $fields = [];
        $fields['name'] = (new InputText())
            ->setLabel(__('Title'))
            ->setPlaceholder(__('Enter a topic name'))
            ->setNameAndId('name')
            ->setValue($this->getValue('name'))
            ->setValidationRules(
                [
                    'NotEmpty',
                    'StringLength'   => ['min' => 3, 'max' => 200],
                    'ModelNotExists' => [
                        'model'   => ForumTopic::class,
                        'field'   => 'name',
                        'exclude' => function (Builder $query) {
                            $query->where('section_id', $this->sectionId)
                                ->when($this->hasValues(), function (Builder $builder) {
                                    return $builder->where('id', '!=', $this->getValue('id'));
                                });
                        },
                    ],
                ]
            );

        if (! $this->hasValues()) {
            $fields['message'] = (new Textarea())
                ->setLabel(__('Message'))
                ->setPlaceholder(__('Enter a message'))
                ->setNameAndId('message')
                ->setValue($this->getValue('message'))
                ->setValidationRules(['NotEmpty']);

            $fields['add_file'] = (new Checkbox())
                ->setLabel(__('Add File'))
                ->setNameAndId('add_file')
                ->setValue('yes')
                ->setChecked(! empty($this->getValue('add_file')));
        }

        // Meta tags
        if ($user->hasAnyRole()) {
            $fields['meta_description'] = (new InputText())
                ->setLabel(__('Meta description'))
                ->setPlaceholder(__('Meta description'))
                ->setNameAndId('meta_description')
                ->setValue($this->getValue('meta_description'));

            $fields['meta_keywords'] = (new InputText())
                ->setLabel(__('Meta keywords'))
                ->setPlaceholder(__('Meta keywords'))
                ->setNameAndId('meta_keywords')
                ->setValue($this->getValue('meta_keywords'));
        }

        return $fields;
    }
}
