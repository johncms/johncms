<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\Controllers\Admin;

use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\Http\View\ViewResponse;
use Johncms\Modules\Admin\Domain\Exceptions\ConfigWriteException;
use Johncms\Modules\News\Application\DTO\NewsSettingsDTO;
use Johncms\Modules\News\Application\Exceptions\NewsSettingsException;
use Johncms\Modules\News\Application\Services\NewsListControlsMapper;
use Johncms\Modules\News\Application\UseCases\GetNewsSettingsUseCase;
use Johncms\Modules\News\Application\UseCases\UpdateNewsSettingsUseCase;
use Johncms\NavChain;

/**
 * The settings of the news: the block of the home page and the meta tags of the pages.
 */
final readonly class NewsSettingsController
{
    private const URL = '/admin/news/settings';

    public function __construct(
        private NavChain $navChain,
        private GetNewsSettingsUseCase $getSettings,
        private UpdateNewsSettingsUseCase $updateSettings,
        private Session $session,
    ) {
    }

    public function form(): ViewResponse
    {
        return $this->render($this->getSettings->execute(), []);
    }

    public function save(Request $request): ViewResponse
    {
        $settings = new NewsSettingsDTO(
            homepageShow: $request->hasBody('homepage_show'),
            homepageQuantity: $request->bodyInt('homepage_quantity'),
            homepageDays: $request->bodyInt('homepage_days'),
            title: $request->body('title'),
            metaKeywords: $request->body('meta_keywords'),
            metaDescription: $request->body('meta_description'),
            sectionTitle: $request->body('section_title'),
            sectionMetaKeywords: $request->body('section_meta_keywords'),
            sectionMetaDescription: $request->body('section_meta_description'),
            articleTitle: $request->body('article_title'),
            articleMetaKeywords: $request->body('article_meta_keywords'),
            articleMetaDescription: $request->body('article_meta_description'),
        );

        try {
            $this->updateSettings->execute($settings);
        } catch (NewsSettingsException $e) {
            return $this->render($settings, $e->errors);
        } catch (ConfigWriteException) {
            return $this->render($settings, [], __('ERROR: Can not write file `news.local.php`'));
        }

        $this->session->flash('success_message', __('Settings saved!'));
        redirect(self::URL);
    }

    /**
     * @param array<string, list<string>> $errors
     */
    private function render(NewsSettingsDTO $settings, array $errors, string $errorMessage = ''): ViewResponse
    {
        $title = __('Settings');
        $this->navChain->add(__('News'), NewsListControlsMapper::PATH);
        $this->navChain->add($title);

        return new ViewResponse('@news/admin/settings.twig', [
            'title'           => $title,
            'page_title'      => __('News settings'),
            'module_menu'     => ['news' => true],
            'settings'        => $settings,
            'errors'          => $errors,
            'form_action'     => self::URL,
            'list_url'        => NewsListControlsMapper::PATH,
            'max_quantity'    => UpdateNewsSettingsUseCase::MAX_QUANTITY,
            'max_days'        => UpdateNewsSettingsUseCase::MAX_DAYS,
            'error_message'   => $errorMessage,
            'success_message' => (string) $this->session->getFlash('success_message'),
        ]);
    }
}
