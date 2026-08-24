<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Admin;

use Gettext\Translator;
use Gettext\TranslatorFunctions;
use Johncms\Modules\Admin\Application\DTO\CaptchaSettingRowDTO;
use Johncms\View\Twig\Extension\I18nExtension;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/**
 * The component the settings of a provider are drawn with.
 *
 * Rendered rather than read, because what can go wrong here is silent: the template walks the
 * fields a provider declared, and a renamed property of the row would only show up as an empty
 * input on a screen nobody opens until they need it.
 */
final class SettingsFieldTemplateTest extends TestCase
{
    private const TEMPLATE = '@admin/components/settings-field.twig';

    protected function setUp(): void
    {
        TranslatorFunctions::register(new Translator());
    }

    public function testATextFieldCarriesItsValueAndHint(): void
    {
        $html = $this->render(
            new CaptchaSettingRowDTO(
                key: 'width',
                type: 'number',
                label: 'Picture width',
                hint: 'A narrow picture crowds the characters',
                value: '250',
            )
        );

        self::assertStringContainsString('name="providers[image][width]"', $html);
        self::assertStringContainsString('value="250"', $html);
        self::assertStringContainsString('type="number"', $html);
        self::assertStringContainsString('A narrow picture crowds the characters', $html);
    }

    /**
     * A stored secret is reported as stored and never printed.
     */
    public function testAPasswordFieldIsEmptyAndSaysWhetherItIsSet(): void
    {
        $html = $this->render(
            new CaptchaSettingRowDTO(
                key: 'secret_key',
                type: 'password',
                label: 'Secret key',
                value: 'must-not-appear',
                hasValue: true,
            )
        );

        self::assertStringContainsString('type="password"', $html);
        self::assertStringContainsString('value=""', $html);
        self::assertStringNotContainsString('must-not-appear', $html);
        self::assertStringContainsString('Saved. Fill in to replace.', $html);
    }

    public function testASelectMarksTheStoredValue(): void
    {
        $html = $this->render(
            new CaptchaSettingRowDTO(
                key: 'format',
                type: 'select',
                label: 'Picture format',
                value: 'webp',
                options: ['png' => 'PNG', 'webp' => 'WebP'],
            )
        );

        self::assertStringContainsString('<option value="webp" selected>WebP</option>', $html);
        self::assertStringContainsString('<option value="png" >PNG</option>', $html);
    }

    public function testACheckboxIsCheckedFromTheRow(): void
    {
        $html = $this->render(
            new CaptchaSettingRowDTO(key: 'enabled', type: 'checkbox', label: 'Enabled', checked: true)
        );

        self::assertStringContainsString('type="checkbox"', $html);
        self::assertStringContainsString('checked', $html);
    }

    private function render(CaptchaSettingRowDTO $field): string
    {
        $loader = new FilesystemLoader();
        $loader->addPath(MODULES_PATH . 'johncms/admin/templates', 'admin');

        $twig = new Environment($loader, ['autoescape' => 'html', 'cache' => false, 'strict_variables' => true]);
        $twig->addExtension(new I18nExtension());

        return $twig->render(
            self::TEMPLATE,
            [
                'field' => $field,
                'name'  => 'providers[image][' . $field->key . ']',
                'id'    => 'image_' . $field->key,
            ]
        );
    }
}
