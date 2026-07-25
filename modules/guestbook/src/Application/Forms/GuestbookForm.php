<?php

declare(strict_types=1);

namespace Johncms\Modules\Guestbook\Application\Forms;

use Johncms\Modules\Guestbook\Domain\Models\GuestbookEntry;
use Johncms\Http\Request;
use Johncms\System\Utility\EditorContentNormalizer;
use Johncms\Users\User;

class GuestbookForm
{
    public function __construct(
        private readonly Request $request,
        private readonly User $user,
        private readonly EditorContentNormalizer $editorContentNormalizer,
    ) {
    }

    public function getFormData(): array
    {
        $form_data = [
            'name'       => $this->request->body('name', ''),
            'message'    => $this->editorContentNormalizer->trimEdgeEmptyBlocks($this->request->body('message', '')),
            'csrf_token' => $this->request->body('csrf_token', ''),
            'code'       => $this->request->body('code', ''),
        ];
        $form_data = array_map('trim', $form_data);
        $form_data['attached_files'] = (array) $this->request->bodyInts('attached_files');

        return $form_data;
    }

    /**
     * @return array[]
     * @psalm-suppress MissingClosureReturnType,MissingClosureParamType
     */
    public function getValidationRules(): array
    {
        $rules = [
            'message'    => [
                'NotEmpty',
                'StringLength'   => ['min' => 4],
                'ModelNotExists' => [
                    'model'   => GuestbookEntry::class,
                    'field'   => 'text',
                    'exclude' => function ($query) {
                        $query->where('user_id', $this->user->id)->where('time', '>', (time() - 600));
                    },
                ],
            ],
            'csrf_token' => [
                'Csrf',
                'Flood',
                'Ban' => [
                    'bans' => [1, 13],
                ],
            ],
        ];

        if (! $this->user->isValid()) {
            $rules['name'] = [
                'NotEmpty',
                'StringLength' => ['min' => 3, 'max' => 25],
            ];
            $rules['code'] = [
                'Captcha',
            ];
        }

        return $rules;
    }
}
