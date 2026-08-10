<?php

declare(strict_types=1);

namespace Johncms\Modules\Guestbook\Application\Forms;

use Johncms\Modules\Guestbook\Domain\Models\GuestbookEntry;
use Johncms\Http\Request;
use Johncms\System\Utility\EditorContentNormalizer;
use Johncms\Users\User;
use Johncms\Validator\Rules\Ban;
use Johncms\Validator\Rules\Captcha;
use Johncms\Validator\Rules\Flood;
use Johncms\Validator\Rules\ModelNotExists;
use Johncms\Validator\Rules\RuleInterface;
use Johncms\Validator\Rules\StringLength;
use Johncms\Validator\ValidationResult;

class GuestbookForm
{
    public function __construct(
        private readonly User $user,
        private readonly EditorContentNormalizer $editorContentNormalizer,
    ) {
    }

    public function getFormData(Request $request): array
    {
        $form_data = [
            'name'       => $request->body('name', ''),
            'message'    => $this->editorContentNormalizer->trimEdgeEmptyBlocks($request->body('message', '')),
            'code'       => $request->body('code', ''),
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
                        $query->where('user_id', $this->user->id)->where('time', '>', (time() - 600));
                    },
                ),
            ],
            ValidationResult::FORM_KEY => [
                new Flood(),
                new Ban(bans: [1, 13]),
            ],
        ];

        if (! $this->user->isValid()) {
            $rules['name'] = [new StringLength(min: 3, max: 25)];
            $rules['code'] = [new Captcha()];
        }

        return $rules;
    }
}
