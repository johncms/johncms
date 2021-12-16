<?php

declare(strict_types=1);

namespace Johncms\Personal\Forms;

use Johncms\Forms\AbstractForm;
use Johncms\Forms\Inputs\Checkbox;
use Johncms\Forms\Inputs\InputText;
use Johncms\Forms\Inputs\Select;
use Johncms\i18n\Languages;
use Johncms\Users\User;
use Johncms\View\Themes;

class SettingsForm extends AbstractForm
{
    public function __construct(
        protected ?User $userData = null
    ) {
        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    protected function prepareFormFields(): array
    {
        $fields = [];
        $fields['lang'] = (new Select())
            ->setOptions($this->getLanguages())
            ->setLabel(__('Language'))
            ->setNameAndId('lang')
            ->setValue($this->getValue('lang'));

        $fields['timezone'] = (new Select())
            ->setOptions($this->getTimezones())
            ->setLabel(__('Timezone'))
            ->setNameAndId('timezone')
            ->setValue($this->getValue('timezone'));

        $fields['directUrl'] = (new Checkbox())
            ->setLabel(__('Direct links'))
            ->setNameAndId('directUrl')
            ->setHelpText(__('When this option is enabled, links to other sites will open without an intermediate page.'))
            ->setValue(true)
            ->setChecked((bool) $this->getValue('directUrl'));

        $fields['perPage'] = (new InputText())
            ->setLabel(__('Elements per page'))
            ->setPlaceholder(__('Elements per page'))
            ->setNameAndId('perPage')
            ->setValue($this->getValue('perPage'));

        $fields['theme'] = (new Select())
            ->setOptions($this->getThemesList())
            ->setLabel(__('Site theme'))
            ->setNameAndId('theme')
            ->setValue($this->getValue('theme'));

        return $fields;
    }

    public function getValue(string $fieldName, mixed $default = null)
    {
        if ($this->userData) {
            // Base fields
            return parent::getValue($fieldName, $this->userData->settings?->$fieldName);
        }
        return parent::getValue($fieldName, $default);
    }

    private function getThemesList(): array
    {
        $themes = di(Themes::class)->getThemes();
        $themesList = [];
        foreach ($themes as $theme) {
            if ($theme === 'example') {
                continue;
            }
            $themesList[] = [
                'name'  => $theme,
                'value' => $theme,
            ];
        }
        return $themesList;
    }

    // TODO: Change the list of timezones
    private function getTimezones(): array
    {
        return [
            [
                'value' => 'Europe/Moscow',
                'name'  => d__('system', 'Europe/Moscow'),
            ],
            [
                'value' => 'Europe/Berlin',
                'name'  => d__('system', 'Europe/Berlin'),
            ],
            [
                'value' => 'America/New_York',
                'name'  => d__('system', 'America/New_York'),
            ],
        ];
    }

    private function getLanguages(): array
    {
        $lngList = Languages::getLngList();
        $options = [];
        foreach ($lngList as $key => $item) {
            $options[] = [
                'name'  => $item['name'],
                'value' => $key,
            ];
        }

        return $options;
    }
}
