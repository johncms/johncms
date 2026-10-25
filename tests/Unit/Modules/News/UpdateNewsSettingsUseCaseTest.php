<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\News;

use Gettext\Translator;
use Gettext\TranslatorFunctions;
use Johncms\Modules\News\Application\DTO\NewsSettingsDTO;
use Johncms\Modules\News\Application\Exceptions\NewsSettingsException;
use Johncms\Modules\News\Application\UseCases\UpdateNewsSettingsUseCase;
use Johncms\Modules\News\Domain\Repository\NewsConfigRepositoryInterface;
use Johncms\Validator\RuleCompiler;
use Johncms\Validator\RuleFactories\BetweenRuleFactory;
use Johncms\Validator\RuleFactories\StringLengthRuleFactory;
use Johncms\Validator\SymfonyValidator;
use Johncms\Validator\Translation\GettextTranslator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Tests\Support\FakeNewsConfigRepository;

/**
 * What the settings of the news write into the configuration, and what they refuse.
 */
final class UpdateNewsSettingsUseCaseTest extends TestCase
{
    protected function setUp(): void
    {
        TranslatorFunctions::register(new Translator());
    }

    public function testEverySettingIsWrittenAsTyped(): void
    {
        $config = $this->config();

        $this->useCase($config)->execute(new NewsSettingsDTO(
            homepageShow: true,
            homepageQuantity: 5,
            homepageDays: 30,
            title: ' Our news ',
            metaKeywords: 'news',
            metaDescription: 'What <happens>',
            sectionTitle: '#section_name# — news',
            sectionMetaKeywords: '#section_name#',
            sectionMetaDescription: '',
            articleTitle: '#article_name#',
            articleMetaKeywords: '',
            articleMetaDescription: 'About #article_name#',
        ));

        self::assertSame([
            'homepage_show'            => true,
            'homepage_quantity'        => 5,
            'homepage_days'            => 30,
            'title'                    => 'Our news',
            'meta_keywords'            => 'news',
            'meta_description'         => 'What <happens>',
            'section_title'            => '#section_name# — news',
            'section_meta_keywords'    => '#section_name#',
            'section_meta_description' => '',
            'article_title'            => '#article_name#',
            'article_meta_keywords'    => '',
            'article_meta_description' => 'About #article_name#',
        ], $config->saved);
    }

    public function testNumbersOutsideTheirRangeAreRefusedAndNothingIsWritten(): void
    {
        foreach ([[0, 0, 'homepage_quantity'], [51, 0, 'homepage_quantity'], [3, -1, 'homepage_days'], [3, 3651, 'homepage_days']] as [$quantity, $days, $field]) {
            $config = $this->config();

            try {
                $this->useCase($config)->execute(new NewsSettingsDTO(homepageShow: true, homepageQuantity: $quantity, homepageDays: $days));
                self::fail('Accepted ' . $field . ' of ' . $quantity . '/' . $days);
            } catch (NewsSettingsException $e) {
                self::assertArrayHasKey($field, $e->errors);
            }

            self::assertNull($config->saved);
        }
    }

    public function testATextLongerThanItsFieldIsRefused(): void
    {
        $this->expectException(NewsSettingsException::class);

        $this->useCase($this->config())->execute(new NewsSettingsDTO(homepageShow: true, homepageQuantity: 3, homepageDays: 0, title: str_repeat('a', 251)));
    }

    private function useCase(NewsConfigRepositoryInterface $config): UpdateNewsSettingsUseCase
    {
        $engine = Validation::createValidatorBuilder()->setTranslator(new GettextTranslator())->getValidator();

        return new UpdateNewsSettingsUseCase($config, new SymfonyValidator($engine, new RuleCompiler([
            new StringLengthRuleFactory(),
            new BetweenRuleFactory(),
        ])));
    }

    private function config(): FakeNewsConfigRepository
    {
        return new FakeNewsConfigRepository();
    }
}
