<?php

declare(strict_types=1);

namespace Johncms\Modules\Guestbook\Application\Forms;

use Johncms\Auth\CurrentUser;
use Johncms\Captcha\CaptchaManager;
use Johncms\Modules\Guestbook\Domain\Models\GuestbookEntry;
use Johncms\Http\Request;
use Johncms\System\Utility\EditorContentNormalizer;
use Johncms\Validator\Rules\Ban;
use Johncms\Validator\Rules\Captcha;
use Johncms\Validator\Rules\Flood;
use Johncms\Validator\Rules\ModelNotExists;
use Johncms\Validator\Rules\RuleInterface;
use Johncms\Validator\Rules\StringLength;
use Johncms\Validator\ValidationResult;

class GuestbookForm
{
    public const CAPTCHA_SCOPE = 'guestbook';

    public function __construct(
        private readonly CurrentUser $currentUser,
        private readonly EditorContentNormalizer $editorContentNormalizer,
        private readonly CaptchaManager $captcha,
    ) {
    }

    public function getFormData(Request $request): array
    {
        $form_data = [
            'name'       => $request->body('name', ''),
            'message'    => $this->editorContentNormalizer->trimEdgeEmptyBlocks($request->body('message', '')),
            // The name of the field belongs to the captcha in use: the built-in one answers in
            // `code`, a remote service in a field of its own.
            'code'       => $request->body($this->captcha->fieldName(), ''),
        ];
        $form_data = array_map('trim', $form_data);
        $form_data['attached_files'] = (array) $request->bodyInts('attached_files');

        return $form_data;
    }

    /**
     * @return array<string, list<RuleInterface>>
     */
    public function getValidationRules(): array
    {
        $rules = [
            'message' => [
                new StringLength(min: 4),
                new ModelNotExists(
                    model: GuestbookEntry::class,
                    field: 'text',
                    // The same message posted twice within ten minutes by the same visitor is a
                    // duplicate; an identical message from somebody else is not.
                    exclude: function ($query): void {
                        $query->where('user_id', $this->currentUser->id())->where('time', '>', (time() - 600));
                    },
                ),
            ],
            ValidationResult::FORM_KEY => [
                new Flood(),
                new Ban(bans: [1, 13]),
            ],
        ];

        if (! $this->currentUser->isValid()) {
            $rules['name'] = [new StringLength(min: 3, max: 25)];
            $rules['code'] = [new Captcha(scope: self::CAPTCHA_SCOPE)];
        }

        return $rules;
    }
}
