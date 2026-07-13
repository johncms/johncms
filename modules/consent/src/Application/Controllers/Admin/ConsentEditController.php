<?php

declare(strict_types=1);

namespace Johncms\Modules\Consent\Application\Controllers\Admin;

use Johncms\Http\Controller\AdminControllerContext;
use Johncms\Modules\Consent\Application\DTO\ConsentDTO;
use Johncms\Modules\Consent\Application\UseCases\CreateConsentUseCase;
use Johncms\Modules\Consent\Application\UseCases\UpdateConsentUseCase;
use Johncms\Modules\Consent\Domain\Models\Consent;
use Johncms\Modules\Consent\Domain\Repository\ConsentRepositoryInterface;
use Johncms\NavChain;
use Johncms\System\Http\Request;
use Johncms\System\i18n\Translator;
use Johncms\System\View\Render;
use Johncms\Validator\Validator;

final readonly class ConsentEditController
{
    private const URL = '/admin/consents';

    /** Known form contexts, offered as suggestions in the admin form. */
    private const KNOWN_CONTEXTS = ['register', 'contacts'];

    /**
     * The title may carry inline HTML with links, so the column is TEXT.
     *
     * TEXT holds 65535 bytes, while the rule counts characters: a utf8mb4 character takes up
     * to 4 bytes, so this is the largest limit that can never overflow the column.
     */
    private const TITLE_MAX_LENGTH = 16383;

    public function __construct(
        private AdminControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private NavChain $navChain,
        private Translator $translator,
        private ConsentRepositoryInterface $repository,
        private CreateConsentUseCase $createConsent,
        private UpdateConsentUseCase $updateConsent,
    ) {
        $this->controllerContext->initModule('consent');
    }

    public function __invoke(?int $id = null): string
    {
        $consent = null;
        if ($id !== null) {
            $consent = $this->repository->findById($id);
            if ($consent === null) {
                redirect(self::URL);
            }
        }

        $fields = $this->initialFields($consent);
        $errors = [];

        $lngList = config('johncms')['lng_list'] ?? [];
        $languageCodes = array_keys($lngList);
        $languages = [];
        foreach ($lngList as $code => $data) {
            $languages[] = ['code' => $code, 'name' => $data['name'] ?? $code];
        }

        if ($this->request->getMethod() === 'POST') {
            $fields = $this->fieldsFromRequest();
            $validator = new Validator(
                [
                    'context'    => $fields['context'],
                    'language'   => $fields['language'],
                    'title'      => $fields['title'],
                    'version'    => $fields['version'],
                    'csrf_token' => (string) $this->request->getPost('csrf_token', ''),
                ],
                [
                    'context'    => ['NotEmpty', 'StringLength' => ['max' => 100]],
                    'language'   => ['InArray' => ['haystack' => $languageCodes]],
                    'title'      => ['NotEmpty', 'StringLength' => ['max' => self::TITLE_MAX_LENGTH]],
                    'version'    => ['NotEmpty', 'StringLength' => ['max' => 50]],
                    'csrf_token' => ['Csrf'],
                ]
            );

            if ($validator->isValid()) {
                $dto = new ConsentDTO(
                    context: $fields['context'],
                    language: $fields['language'],
                    title: $fields['title'],
                    text: $fields['text'],
                    version: $fields['version'],
                    isRequired: $fields['is_required'],
                    isActive: $fields['is_active'],
                );

                if ($consent instanceof Consent) {
                    $this->updateConsent->execute($consent, $dto);
                } else {
                    $this->createConsent->execute($dto);
                }

                $_SESSION['success_message'] = __('Changes saved successfully');
                redirect(self::URL);
            }

            $errors = $validator->getErrors();
        }

        $isEdit = $consent instanceof Consent;
        $title = $isEdit ? __('Edit consent') : __('Add consent');

        $this->navChain->add(__('Consents'), self::URL);
        $this->navChain->add($title);
        $this->render->addData([
            'title'       => $title,
            'page_title'  => $title,
            'module_menu' => ['consents' => true],
        ]);

        return $this->render->render('consent::admin/form', [
            'edit_form'   => $isEdit,
            'form_action' => $isEdit ? self::URL . '/' . $consent->id . '/edit' : self::URL . '/create',
            'back_url'    => self::URL,
            'fields'      => $fields,
            'errors'      => $errors,
            'contexts'    => self::KNOWN_CONTEXTS,
            'languages'   => $languages,
            'locale'      => $this->translator->getLocale(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function initialFields(?Consent $consent): array
    {
        return [
            'context'     => $consent->context ?? '',
            'language'    => $consent->language ?? (string) (config('johncms')['lng'] ?? 'en'),
            'title'       => $consent->title ?? '',
            'text'        => $consent->text ?? '',
            'version'     => $consent->version ?? '1.0',
            'is_required' => $consent->is_required ?? true,
            'is_active'   => $consent->is_active ?? true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function fieldsFromRequest(): array
    {
        return [
            'context'     => trim((string) $this->request->getPost('context', '')),
            'language'    => trim((string) $this->request->getPost('language', '')),
            'title'       => trim((string) $this->request->getPost('title', '')),
            'text'        => (string) $this->request->getPost('text', ''),
            'version'     => trim((string) $this->request->getPost('version', '')),
            'is_required' => $this->request->getPost('is_required') !== null,
            'is_active'   => $this->request->getPost('is_active') !== null,
        ];
    }
}
